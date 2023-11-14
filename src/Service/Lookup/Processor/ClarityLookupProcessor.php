<?php

declare(strict_types=1);

namespace App\Service\Lookup\Processor;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Lookup\ClarityEdr;
use App\Entity\Lookup\ClarityEdrsRecord;
use App\Entity\Lookup\ClarityPersonCourt;
use App\Entity\Lookup\ClarityPersonCourtsRecord;
use App\Entity\Lookup\ClarityPersonEdr;
use App\Entity\Lookup\ClarityPersonEdrsRecord;
use App\Entity\Lookup\ClarityPersonEnforcement;
use App\Entity\Lookup\ClarityPersonEnforcementsRecord;
use App\Entity\Lookup\ClarityPersonSecurity;
use App\Entity\Lookup\ClarityPersonSecurityRecord;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Lookup\LookupProcessorName;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class ClarityLookupProcessor implements LookupProcessorInterface
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
        if ($this->supportsPersonName($searchTerm, $context)) {
            return true;
        }

        if ($this->supportsOrganizationName($searchTerm, $context)) {
            return true;
        }

        return false;
    }

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array
    {
        $records = [];

        $name = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

        if ($this->supportsPersonName($searchTerm, $context)) {
            $jobs = [
                fn (string $name) => $this->getPersonSecurityRecord($name),
                fn (string $name) => $this->getPersonEdrsRecord($name),
                fn (string $name) => $this->getPersonCourtsRecord($name),
                fn (string $name) => $this->getPersonEnforcementsRecord($name),
            ];
        } elseif ($this->supportsOrganizationName($searchTerm, $context)) {
            $jobs = [
                fn (string $name) => $this->getEdrsRecord($name),
            ];
        } else {
            $jobs = [];
        }

        foreach ($jobs as $job) {
            try {
                $records[] = $job($name);
            } catch (Throwable $exception) {
                $this->logger->error($exception);
            }
        }

        return array_filter($records);
    }

    private function supportsPersonName(FeedbackSearchTerm $searchTerm, array $context = []): bool
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

    private function supportsOrganizationName(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        if ($this->environment === 'test') {
            return false;
        }

        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        if ($searchTerm->getType() !== SearchTermType::organization_name) {
            return false;
        }

        $name = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

        if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $name) !== 1) {
            return false;
        }

        return true;
    }

    private function getPersonEdrsRecord(string $name): ?ClarityPersonEdrsRecord
    {
        $record = new ClarityPersonEdrsRecord();

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

            if ($tds->eq(0)->count() < 1) {
                return;
            }

            $name = trim($tds->eq(0)->text() ?? '');

            if (empty($name)) {
                return;
            }

            $active = str_contains($name, 'Зареєстровано');
            $name = trim(str_replace(['Стан: Зареєстровано', 'Стан: Припинено', 'Зареєстровано', 'Припинено'], '', $name));

            if ($tr->filter('a')->eq(0)->count() > 0) {
                $href = trim($tr->filter('a')->eq(0)->attr('href') ?? '');
                $href = empty($href) ? null : ($baseUri . $href);
            }

            if (isset($header[1]) && str_contains($header[1], 'ЄДРПОУ') && $tds->eq(1)->count() > 0) {
                $number = preg_replace('/[^0-9]/', '', trim($tds->eq(1)->text() ?? ''));
            }

            if (isset($header[2]) && $tds->eq(2)->count() > 0) {
                $address = trim($tds->eq(2)->text() ?? '');
            }

            if (isset($header[3]) && $tds->eq(3)->count() > 0) {
                $type = trim($tds->eq(3)->text() ?? '');
            }

            $edr = new ClarityPersonEdr(
                $name,
                type: $type ?? null,
                href: $href ?? null,
                number: $number ?? null,
                active: $active,
                address: $address ?? null
            );

            $record->addEdr($edr);
        });

        return count($record->getEdrs()) === 0 ? null : $record;
    }

    private function getPersonCourtsRecord(string $name): ?ClarityPersonCourtsRecord
    {
        $record = new ClarityPersonCourtsRecord();

        $crawler = $this->getCrawler('/person/' . $name);

        $table = $crawler->filter('[data-id="court-involved"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'справи')) {
            return null;
        }

        $table->filter('tr.item')->each(static function (Crawler $tr) use ($header, $record): void {
            $tds = $tr->filter('td');

            if ($tds->eq(0)->count() < 1) {
                return;
            }

            $number = trim($tds->eq(0)->text() ?? '');

            if (empty($number)) {
                return;
            }

            if (isset($header[1]) && str_contains($header[1], 'Стан') && $tds->eq(1)->count() > 0) {
                $state = trim($tds->eq(1)->text() ?? '');
            }

            if (isset($header[2]) && str_contains($header[2], 'Сторона') && $tds->eq(2)->count() > 0) {
                $side = trim($tds->eq(2)->text() ?? '');
            }

            if (isset($header[3]) && str_contains($header[3], 'Опис') && $tds->eq(3)->count() > 0) {
                $desc = trim($tds->eq(3)->text() ?? '');
            }

            if (isset($header[4]) && str_contains($header[4], 'Суд') && $tds->eq(4)->count() > 0) {
                $place = trim($tds->eq(4)->text() ?? '');
            }

            $court = new ClarityPersonCourt(
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

    private function getPersonSecurityRecord(string $name): ?ClarityPersonSecurityRecord
    {
        $record = new ClarityPersonSecurityRecord();

        $crawler = $this->getCrawler('/person/' . $name);

        $table = $crawler->filter('[data-id="security"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'ПІБ')) {
            return null;
        }

        $table->filter('tr.item')->each(static function (Crawler $tr) use ($header, $record): void {
            $tds = $tr->filter('td');

            if ($tds->eq(0)->count() < 1) {
                return;
            }

            $name = trim($tds->eq(0)->text() ?? '');

            if (empty($name)) {
                return;
            }

            if (isset($header[1]) && str_contains($header[1], 'народження') && $tds->eq(1)->count() > 0) {
                $bornAt = trim($tds->eq(1)->text() ?? '');
                $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt);
                $bornAt = $bornAt === false ? null : $bornAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Категорія') && $tds->eq(2)->count() > 0) {
                $category = trim($tds->eq(2)->text() ?? '');
            }

            if (isset($header[3]) && str_contains($header[3], 'Регіон') && $tds->eq(3)->count() > 0) {
                $region = trim($tds->eq(3)->text() ?? '');
            }

            if (isset($header[4]) && str_contains($header[4], 'зникнення') && $tds->eq(4)->count() > 0) {
                if ($tds->eq(4)->filter('.small')->count() > 0) {
                    $archive = trim($tds->eq(4)->filter('.small')->text() ?? '');
                    $archive = empty($archive) ? null : str_contains($archive, 'архівна');
                }

                if (!empty($tds->eq(4)->getNode(0)?->firstChild?->textContent)) {
                    $absentAt = trim($tds->eq(4)->getNode(0)->firstChild->textContent ?? '');
                    $absentAt = empty($absentAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $absentAt);
                    $absentAt = $absentAt === false ? null : $absentAt;
                }
            }

            $accusation = null;
            $precaution = null;

            $nextTr = $tr->nextAll();

            if ($nextTr->matches('.table-collapse-details')) {
                $trs = $nextTr->filter('tr');
                if ($trs->eq(5)->filter('td')->eq(0)->count() > 0 && str_contains($trs->eq(5)->filter('td')->eq(0)->text(), 'Звинувачення')) {
                    $accusation = trim($trs->eq(5)->filter('td')->eq(1)->text() ?? '');
                }
                if ($trs->eq(6)->filter('td')->eq(0)->count() > 0 && str_contains($trs->eq(6)->filter('td')->eq(0)->text(), 'Запобіжний')) {
                    $precaution = trim($trs->eq(6)->filter('td')->eq(1)->text() ?? '');
                }
            }

            $security = new ClarityPersonSecurity(
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

    private function getPersonEnforcementsRecord(string $name): ?ClarityPersonEnforcementsRecord
    {
        $record = new ClarityPersonEnforcementsRecord();

        $crawler = $this->getCrawler('/person/' . $name);

        $table = $crawler->filter('[data-id="enforcements"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'в/п')) {
            return null;
        }

        $table->filter('tr.item')->each(static function (Crawler $tr) use ($header, $record): void {
            $tds = $tr->filter('td');

            if ($tds->eq(0)->count() < 1) {
                return;
            }

            $number = trim($tds->eq(0)->text());

            if (empty($number)) {
                return;
            }

            if (isset($header[1]) && str_contains($header[1], 'відкриття') && $tds->eq(1)->count() > 0) {
                $openedAt = trim($tds->eq(1)->text() ?? '');
                $openedAt = empty($openedAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $openedAt);
                $openedAt = $openedAt === false ? null : $openedAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Стягувач') && $tds->eq(2)->count() > 0) {
                $collector = trim($tds->eq(2)->text() ?? '');
            }

            if (isset($header[3]) && str_contains($header[3], 'Боржник') && $tds->eq(3)->count() > 0) {
                $debtor = trim($tds->eq(3)->text() ?? '');

                if ($tds->count() === count($header) + 1) {
                    if ($tds->eq(4)->count() > 0) {
                        $bornAt = trim($tds->eq(4)->text() ?? '');
                        $peaces = explode(' ', $bornAt);
                        $bornAt = $peaces[count($peaces) - 1];
                        $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt);
                        $bornAt = $bornAt === false ? null : $bornAt;
                    }
                }
            }

            if (isset($header[4]) && str_contains($header[4], 'Стан') && $tds->eq($tds->count() - 1)->count() > 0) {
                $state = trim($tds->eq($tds->count() - 1)?->text() ?? '');
            }

            $enforcement = new ClarityPersonEnforcement(
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

    private function getEdrsRecord(string $name): ?ClarityEdrsRecord
    {
        $record = new ClarityEdrsRecord();

        $crawler = $this->getCrawler('/edrs/?search=' . $name);
        $baseUri = $this->getBaseUri();

        $crawler->filter('.list .item')->each(static function (Crawler $item) use ($baseUri, $record): void {
            if ($item->filter('.name')->count() < 1) {
                return;
            }

            $name = addslashes(trim($item->filter('.name')->text() ?? ''));

            if (empty($name)) {
                return;
            }

            if ($item->filter('a')->eq(0)->count() > 0) {
                $href = trim($item->filter('a')->eq(0)->attr('href') ?? '');
                $href = empty($href) ? null : ($baseUri . $href);
            }

            if ($item->filter('.edr')->count() > 0) {
                $number = preg_replace('/[^0-9]/', '', trim($item->filter('.edr')->text() ?? ''));
            }

            if ($item->filter('.address')->count() > 0) {
                $address = trim($item->filter('.address')->text() ?? '');
                $address = trim(str_replace(['Адреса:'], '', $address));
            }

            if ($item->filter('.source-info')->count() > 0) {
                if (str_contains($item->filter('.source-info')->text(), 'ФОП')) {
                    $type = 'ФОП';
                }
            }

            $edr = new ClarityEdr(
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
