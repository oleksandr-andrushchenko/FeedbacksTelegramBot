<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Clarity\ClarityEdr;
use App\Entity\Search\Clarity\ClarityEdrsRecord;
use App\Entity\Search\Clarity\ClarityPerson;
use App\Entity\Search\Clarity\ClarityPersonCourt;
use App\Entity\Search\Clarity\ClarityPersonCourtsRecord;
use App\Entity\Search\Clarity\ClarityPersonDebtor;
use App\Entity\Search\Clarity\ClarityPersonDebtorsRecord;
use App\Entity\Search\Clarity\ClarityPersonEdr;
use App\Entity\Search\Clarity\ClarityPersonEdrsRecord;
use App\Entity\Search\Clarity\ClarityPersonEnforcement;
use App\Entity\Search\Clarity\ClarityPersonEnforcementsRecord;
use App\Entity\Search\Clarity\ClarityPersonSecurity;
use App\Entity\Search\Clarity\ClarityPersonSecurityRecord;
use App\Entity\Search\Clarity\ClarityPersonsRecord;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\CrawlerProvider;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://clarity-project.info/persons
 */
class ClaritySearchProvider implements SearchProviderInterface
{
    public function __construct(
        private readonly string $environment,
        private readonly CrawlerProvider $crawlerProvider,
    )
    {
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::clarity;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        if ($this->supportsPersonName($searchTerm->getType(), $searchTerm->getNormalizedText(), $context)) {
            return true;
        }

        if ($this->supportsOrganizationName($searchTerm->getType(), $searchTerm->getNormalizedText(), $context)) {
            return true;
        }

        if ($this->supportsPhoneNumber($searchTerm->getType(), $searchTerm->getNormalizedText())) {
            return true;
        }

        return false;
    }

    public function getSearchers(FeedbackSearchTerm $searchTerm, array $context = []): iterable
    {
        if ($this->supportsPersonName($searchTerm->getType(), $searchTerm->getNormalizedText(), $context)) {
            $record = $this->searchPersonsRecord($searchTerm->getNormalizedText());

            if ($record === null) {
                return;
            }

            if (count($record->getPersons()) === 1) {
                $name = $record->getPersons()[0]->getName();

                yield fn () => [$this->searchPersonSecurityRecord($name)];
                yield fn () => [$this->searchPersonCourtsRecord($name)];
                yield fn () => [$this->searchPersonDebtorsRecord($name)];
                yield fn () => [$this->searchPersonEnforcementsRecord($name)];
                yield fn () => [$this->searchPersonEdrsRecord($name)];

                return;
            }

            yield fn () => [$record];

            return;
        }

        if ($this->supportsOrganizationName($searchTerm->getType(), $searchTerm->getNormalizedText(), $context)) {
            yield fn () => [$this->searchEdrsRecord($searchTerm->getNormalizedText())];

            return;
        }

        if ($this->supportsPhoneNumber($searchTerm->getType(), $searchTerm->getNormalizedText())) {
            yield fn () => [$this->searchEdrsRecord($searchTerm->getNormalizedText())];

            return;
        }

        yield from [];
    }

    private function supportsPersonName(SearchTermType $type, string $name, array $context = []): bool
    {
        if ($this->environment === 'test') {
            return false;
        }

        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        if ($type !== SearchTermType::person_name) {
            return false;
        }

        if (count(explode(' ', $name)) === 1) {
            return false;
        }

        if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $name) !== 1) {
            return false;
        }

        return true;
    }

    private function supportsOrganizationName(SearchTermType $type, string $name, array $context = []): bool
    {
        if ($this->environment === 'test') {
            return false;
        }

        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        if ($type === SearchTermType::organization_name) {
            if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $name) !== 1) {
                return false;
            }

            return true;
        }

        if ($type === SearchTermType::tax_number) {
            if (!is_numeric($name)) {
                return false;
            }

            if (strlen($name) !== 8) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function supportsPhoneNumber(SearchTermType $type, string $name): bool
    {
        if ($this->environment === 'test') {
            return false;
        }

        if ($type !== SearchTermType::phone_number) {
            return false;
        }

        if (!str_starts_with($name, '38')) {
            return false;
        }

        return true;
    }

    private function searchPersonsRecord(string $name): ?ClarityPersonsRecord
    {
        $record = new ClarityPersonsRecord();

        $crawler = $this->getPersonsCrawler($name);
        $baseUri = $this->getBaseUri();

        $crawler->filter('.list .item')->each(static function (Crawler $item) use ($baseUri, $record): void {
            if ($item->filter('.name')->count() < 1) {
                return;
            }

            $name = trim($item->filter('.name')->text() ?? '');

            if (empty($name)) {
                return;
            }

            if ($item->filter('a')->eq(0)->count() > 0) {
                $href = trim($item->filter('a')->eq(0)->attr('href') ?? '');
                $href = empty($href) ? null : ($baseUri . $href);
            }

            if ($item->filter('.count')->eq(0)->count() > 0) {
                $count = trim($item->filter('.count')->eq(0)->text() ?? '');
                $count = empty($count) ? null : (int) $count;
            }

            $person = new ClarityPerson(
                $name,
                href: $href ?? null,
                count: $count ?? null
            );

            $record->addPerson($person);
        });

        return count($record->getPersons()) === 0 ? null : $record;
    }

    private function searchPersonEdrsRecord(string $name): ?ClarityPersonEdrsRecord
    {
        $record = new ClarityPersonEdrsRecord();

        $crawler = $this->getPersonCrawler($name);
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

    private function searchPersonCourtsRecord(string $name): ?ClarityPersonCourtsRecord
    {
        $record = new ClarityPersonCourtsRecord();

        $crawler = $this->getPersonCrawler($name);

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

    private function searchPersonDebtorsRecord(string $name): ?ClarityPersonDebtorsRecord
    {
        $record = new ClarityPersonDebtorsRecord();

        $crawler = $this->getPersonCrawler($name);

        $table = $crawler->filter('[data-id="debtors"]');
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

            if (isset($header[2]) && str_contains($header[2], 'Запис') && $tds->eq(2)->count() > 0) {
                $category = trim($tds->eq(2)->text() ?? '');
            }

            if (isset($header[3]) && str_contains($header[3], 'Актуально') && $tds->eq(3)->count() > 0) {
                $actualAt = trim($tds->eq(3)->text() ?? '');
                $actualAt = empty($actualAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $actualAt);
                $actualAt = $actualAt === false ? null : $actualAt;
            }

            $debtor = new ClarityPersonDebtor(
                $name,
                bornAt: $bornAt ?? null,
                category: $category ?? null,
                actualAt: $actualAt ?? null,
            );

            $record->addDebtor($debtor);
        });

        return count($record->getDebtors()) === 0 ? null : $record;
    }

    private function searchPersonSecurityRecord(string $name): ?ClarityPersonSecurityRecord
    {
        $record = new ClarityPersonSecurityRecord();

        $crawler = $this->getPersonCrawler($name);

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

    private function searchPersonEnforcementsRecord(string $name): ?ClarityPersonEnforcementsRecord
    {
        $record = new ClarityPersonEnforcementsRecord();

        $crawler = $this->getPersonCrawler($name);

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

    private function searchEdrsRecord(string $name): ?ClarityEdrsRecord
    {
        $record = new ClarityEdrsRecord();

        $crawler = $this->getEdrsCrawler($name);
        $baseUri = $this->getBaseUri();

        $crawler->filter('.list .item')->each(static function (Crawler $item) use ($baseUri, $record): void {
            if ($item->filter('.name')->count() < 1) {
                return;
            }

            $name = trim($item->filter('.name')->text() ?? '');

            if (empty($name)) {
                return;
            }

            if ($item->filter('.source-info')->count() < 1) {
                return;
            }

            $source = $item->filter('.source-info')->text();

            foreach (['ФОП', 'Бенефіціар', 'Засновник'] as $type_) {
                if (str_contains($source, $type_)) {
                    $type = $type_;
                    break;
                }
            }

            if ($item->filter('a')->eq(0)->count() > 0) {
                $href = trim($item->filter('a')->eq(0)->attr('href') ?? '');
                $href = empty($href) ? null : ($baseUri . $href);
            }

            if ($item->filter('.status')->count() > 0) {
                $status = $item->filter('.status')->text();

                if (str_contains($status, 'Припинено')) {
                    $active = false;
                } elseif (str_contains($status, 'Зареєстровано')) {
                    $active = true;
                }
            }

            if ($item->filter('.address')->count() > 0) {
                $address = trim($item->filter('.address')->text() ?? '');
                $address = trim(str_replace(['Адреса:'], '', $address));
            }

            $edr = new ClarityEdr(
                $name,
                type: $type ?? null,
                href: $href ?? null,
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

    private function getPersonsCrawler(string $name): Crawler
    {
        return $this->crawlerProvider->getCrawler('/persons?search=' . $name, baseUri: $this->getBaseUri());
    }

    private function getPersonCrawler(string $name): Crawler
    {
        return $this->crawlerProvider->getCrawler('/person/' . mb_strtoupper($name), baseUri: $this->getBaseUri());
    }

    private function getEdrsCrawler(string $name): Crawler
    {
        return $this->crawlerProvider->getCrawler('/edrs?search=' . $name, baseUri: $this->getBaseUri());
    }
}
