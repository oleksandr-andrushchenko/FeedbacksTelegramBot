<?php

declare(strict_types=1);

namespace App\Service\Lookup\Processor;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Lookup\ClarityProjectCourt;
use App\Entity\Lookup\ClarityProjectCourtsRecord;
use App\Entity\Lookup\ClarityProjectEdr;
use App\Entity\Lookup\ClarityProjectEdrsRecord;
use App\Entity\Lookup\ClarityProjectEnforcement;
use App\Entity\Lookup\ClarityProjectEnforcementsRecord;
use App\Entity\Lookup\ClarityProjectSecurity;
use App\Entity\Lookup\ClarityProjectSecurityRecord;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Lookup\LookupProcessorName;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class ClarityProjectLookupProcessor implements LookupProcessorInterface
{
    public function __construct(
        private readonly string $environment,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function getName(): LookupProcessorName
    {
        return LookupProcessorName::clarity;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        if ($this->environment === 'test') {
            return false;
        }

        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        if ($searchTerm->getType() !== SearchTermType::person_name) {
            return false;
        }

        $name = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

        if (count(explode(' ', $name)) < 2) {
            return false;
        }

        if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $name) !== 1) {
            return false;
        }

        return true;
    }

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array
    {
        $records = [];

        $name = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

        foreach ([
                     fn (string $name) => $this->getSecurityRecord($name),
                     fn (string $name) => $this->getEdrsRecord($name),
                     fn (string $name) => $this->getCourtsRecord($name),
                     fn (string $name) => $this->getEnforcementsRecord($name),
                 ] as $job) {
            try {
                $records[] = $job($name);
            } catch (Throwable $exception) {
                $this->logger->error($exception);
            }
        }

        return array_filter($records);
    }

    private function getEdrsRecord(string $name): ?ClarityProjectEdrsRecord
    {
        $record = new ClarityProjectEdrsRecord();

        $crawler = $this->getCrawler('/person/' . $name);
        $baseUri = $this->getBaseUri();

        // todo: replace with https://clarity-project.info/edrs/?search=%name% (this variant holds addresses for fops)
        // todo: process @mainEntity json

        $table = $crawler->filter('[data-id="edrs"] table')->eq(0);
        $header = [];
        $tr = $table->children('tr')->eq(0);
        $tr->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'Назва')) {
            return null;
        }

        $ids = [];

        $table->children('tr.item')->each(static function (Crawler $tr) use ($baseUri, $header, $record, &$ids): void {
            $id = $tr->attr('data-id');

            if (in_array($id, $ids, true)) {
                return;
            }

            $ids[] = $id;

            $tds = $tr->filter('td');

            $name = trim($tds->eq(0)->text() ?? '');
            $active = str_contains($name, 'Зареєстровано');
            $name = trim(str_replace(['Стан: Зареєстровано', 'Стан: Припинено', 'Зареєстровано', 'Припинено'], '', $name));

            $href = trim($tr->filter('a')?->eq(0)?->attr('href') ?? '');
            $href = empty($href) ? null : ($baseUri . $href);

            if (isset($header[1]) && str_contains($header[1], 'ЄДРПОУ')) {
                $number = preg_replace('/[^0-9]/', '', trim($tds->eq(1)?->text() ?? ''));
            }

            if (isset($header[2])) {
                $address = trim($tds->eq(2)?->text() ?? '');
            }

            if (isset($header[3])) {
                $type = trim($tds->eq(3)?->text() ?? '');
            }

            $edr = new ClarityProjectEdr(
                $name,
                type: $type ?? null,
                href: $href ?? null,
                number: $number ?? null,
                active: $active ?? null,
                address: $address ?? null
            );

            $record->addEdr($edr);
        });

        return count($record->getEdrs()) === 0 ? null : $record;
    }

    private function getCourtsRecord(string $name): ?ClarityProjectCourtsRecord
    {
        $record = new ClarityProjectCourtsRecord();

        $crawler = $this->getCrawler('/person/' . $name);

        $table = $crawler->filter('[data-id="court-involved"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        $table->filter('tr.item')->each(static function (Crawler $tr) use ($header, $record): void {
            $tds = $tr->filter('td');

            if (!isset($header[0]) || !str_contains($header[0], 'справи')) {
                return;
            }

            $number = trim($tds->eq(0)->text());

            if (isset($header[1]) && str_contains($header[1], 'Стан')) {
                $state = trim($tds->eq(1)?->text() ?? '');
            }

            if (isset($header[2]) && str_contains($header[2], 'Сторона')) {
                $side = trim($tds->eq(2)?->text() ?? '');
            }

            if (isset($header[3]) && str_contains($header[3], 'Опис')) {
                $desc = trim($tds->eq(3)?->text() ?? '');
            }

            if (isset($header[4]) && str_contains($header[4], 'Суд')) {
                $place = trim($tds->eq(4)?->text() ?? '');
            }

            $court = new ClarityProjectCourt(
                $number,
                state: $state ?? null,
                side: $side ?? null,
                desc: $desc ?? null,
                place: $place ?? null
            );

            $record->addCourt($court);
        });

        return count($record->getCourts()) === 0 ? null : $record;
    }

    private function getSecurityRecord(string $name): ?ClarityProjectSecurityRecord
    {
        $record = new ClarityProjectSecurityRecord();

        $crawler = $this->getCrawler('/person/' . $name);

        $table = $crawler->filter('[data-id="security"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        $table->filter('tr.item')->each(static function (Crawler $tr) use ($header, $record): void {
            $tds = $tr->filter('td');

            if (!isset($header[0]) || !str_contains($header[0], 'ПІБ')) {
                return;
            }

            $name = trim($tds->eq(0)->text());

            if (isset($header[1]) && str_contains($header[1], 'народження')) {
                $bornAt = trim($tds->eq(1)?->text() ?? null);
                $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt);
                $bornAt = $bornAt === false ? null : $bornAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Категорія')) {
                $category = trim($tds->eq(2)?->text() ?? '');
            }

            if (isset($header[3]) && str_contains($header[3], 'Регіон')) {
                $region = trim($tds->eq(3)?->text() ?? '');
            }

            if (isset($header[4]) && str_contains($header[4], 'зникнення')) {
                $archive = trim($tds->eq(4)?->filter('.small')?->text() ?? '');
                $archive = empty($archive) ? null : str_contains($archive, 'архівна');

                $absentAt = trim($tds->eq(4)?->getNode(0)?->firstChild?->textContent ?? '');
                $absentAt = empty($absentAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $absentAt);
                $absentAt = $absentAt === false ? null : $absentAt;
            }

            $accusation = null;
            $precaution = null;

            $nextTr = $tr->nextAll();

            if ($nextTr->matches('.table-collapse-details')) {
                $trs = $nextTr->filter('tr');
                if (str_contains($trs->eq(5)?->filter('td')?->eq(0)?->text(), 'Звинувачення')) {
                    $accusation = trim($trs->eq(5)->filter('td')->eq(1)?->text() ?? '');
                }
                if (str_contains($trs->eq(6)?->filter('td')?->eq(0)?->text(), 'Запобіжний')) {
                    $precaution = trim($trs->eq(6)->filter('td')->eq(1)?->text() ?? '');
                }
            }

            $security = new ClarityProjectSecurity(
                $name,
                bornAt: $bornAt ?? null,
                category: $category ?? null,
                region: $region ?? null,
                absentAt: $absentAt ?? null,
                archive: $archive ?? null,
                accusation: $accusation ?? null,
                precaution: $precaution ?? null
            );

            $record->addSecurity($security);
        });

        return count($record->getSecurity()) === 0 ? null : $record;
    }

    private function getEnforcementsRecord(string $name): ?ClarityProjectEnforcementsRecord
    {
        $record = new ClarityProjectEnforcementsRecord();

        $crawler = $this->getCrawler('/person/' . $name);

        $table = $crawler->filter('[data-id="enforcements"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        $table->filter('tr.item')->each(static function (Crawler $tr) use ($header, $record): void {
            $tds = $tr->filter('td');

            if (!isset($header[0]) || !str_contains($header[0], 'в/п')) {
                return;
            }

            $number = trim($tds->eq(0)->text());

            if (isset($header[1]) && str_contains($header[1], 'відкриття')) {
                $openedAt = trim($tds->eq(1)?->text() ?? null);
                $openedAt = empty($openedAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $openedAt);
                $openedAt = $openedAt === false ? null : $openedAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Стягувач')) {
                $collector = trim($tds->eq(2)?->text() ?? '');
            }

            if (isset($header[3]) && str_contains($header[3], 'Боржник')) {
                $debtor = trim($tds->eq(3)?->text() ?? '');

                if ($tds->count() === count($header) + 1) {
                    $bornAt = trim($tds->eq(4)?->text() ?? '');
                    $peaces = explode(' ', $bornAt);
                    $bornAt = $peaces[count($peaces) - 1];
                    $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt);
                    $bornAt = $bornAt === false ? null : $bornAt;
                }
            }

            if (isset($header[4]) && str_contains($header[4], 'Стан')) {
                $state = trim($tds->eq($tds->count() - 1)?->text() ?? '');
            }

            $enforcement = new ClarityProjectEnforcement(
                $number,
                openedAt: $openedAt ?? null,
                collector: $collector ?? null,
                debtor: $debtor ?? null,
                bornAt: $bornAt ?? null,
                state: $state ?? null
            );

            $record->addEnforcement($enforcement);
        });

        return count($record->getEnforcements()) === 0 ? null : $record;
    }

    private function getBaseUri(): string
    {
        return 'https://clarity-project.info';
    }

    private function getCrawler(string $uri): Crawler
    {
        static $crawlers = [];

        if (!isset($crawlers[$uri])) {
            if (1 === 1) {
                $url = $this->getBaseUri() . $uri;
                $options = [];

                $response = $this->httpClient->request('GET', $url, $options);

                $status = $response->getStatusCode();

                if ($status !== 200) {
                    throw new RuntimeException(sprintf('Non 200 status code received for "%s" url', $url));
                }

                $content = $response->getContent();
            } else {
                $content = file_get_contents(__DIR__ . '/clarity' . str_replace('/', '.', $uri) . '.html');
            }

            $crawlers[$uri] = new Crawler($content, baseHref: $this->getBaseUri());
        }

        return $crawlers[$uri];
    }
}
