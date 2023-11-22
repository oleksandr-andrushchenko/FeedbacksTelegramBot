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
use App\Entity\Search\Clarity\ClarityPersonDeclaration;
use App\Entity\Search\Clarity\ClarityPersonDeclarationsRecord;
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
 * @see https://clarity-project.info/edrs
 * @see https://clarity-project.info/edr/2762811968/history/prozorro
 * @see https://clarity-project.info/edr/2762811968/persons
 */
class ClaritySearchProvider implements SearchProviderInterface
{
    public const URL = 'https://clarity-project.info';

    public function __construct(
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
        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        if (
            $this->supportsPersonName($type, $term, $context)
            || $this->supportsOrganizationName($type, $term, $context)
            || $this->supportsTaxNumber($type, $term, $context)
            || $this->supportsPhoneNumber($type, $term)
        ) {
            return true;
        }

        return false;
    }

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array
    {
        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        $edrsRecord = $this->searchEdrsRecord($term);

        if ($type === SearchTermType::person_name) {
            $personsRecord = $this->searchPersonsRecord($term);

            if ($personsRecord === null) {
                // todo check person by direct link (+check if 3 words)
                return [
                    $edrsRecord,
                ];
            }

            if (count($personsRecord->getItems()) === 1) {
                $url = $personsRecord->getItems()[0]->getHref();

                return [
                    $this->searchPersonSecurityRecord($url),
                    $this->searchPersonCourtsRecord($url),
                    $this->searchPersonDebtorsRecord($url),
                    $this->searchPersonEnforcementsRecord($url),
                    $this->searchPersonEdrsRecord($url),
                    $this->searchPersonDeclarationsRecord($url),
                    $edrsRecord,
                ];
            }

            return [
                $personsRecord,
                $edrsRecord,
            ];
        }

        // todo parse edr page if single result (implemente the same as for persons made)
        return [
            $edrsRecord,
        ];
    }

    private function supportsPersonName(SearchTermType $type, string $name, array $context = []): bool
    {
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
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        if ($type !== SearchTermType::organization_name) {
            return false;
        }

        if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $name) !== 1) {
            return false;
        }

        return true;
    }

    private function supportsTaxNumber(SearchTermType $type, string $name, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        if ($type !== SearchTermType::tax_number) {
            return false;
        }

        if (!is_numeric($name)) {
            return false;
        }

        if (strlen($name) !== 8) {
            return false;
        }

        return true;
    }

    private function supportsPhoneNumber(SearchTermType $type, string $name): bool
    {
        if ($type !== SearchTermType::phone_number) {
            return false;
        }

        if (!str_starts_with($name, '380')) {
            return false;
        }

        return true;
    }

    private function searchPersonsRecord(string $name): ?ClarityPersonsRecord
    {
        $record = new ClarityPersonsRecord();

        $crawler = $this->getPersonsCrawler($name);

        $crawler->filter('.results-wrap .item')->each(static function (Crawler $item) use ($record): void {
            $a = $item->filter('a');

            if ($a->count() < 1) {
                return;
            }

            $name = trim($a->eq(0)->text());

            if (empty($name)) {
                return;
            }

            $href = trim($a->eq(0)->attr('href') ?? '');
            $href = empty($href) ? null : (self::URL . $href);

            $item = new ClarityPerson(
                $name,
                href: empty($href) ? null : $href,
                count: empty($count) ? null : $count
            );

            $record->addItem($item);
        });

        return count($record->getItems()) === 0 ? null : $record;
    }

    private function searchPersonEdrsRecord(string $url): ?ClarityPersonEdrsRecord
    {
        $record = new ClarityPersonEdrsRecord();

        $crawler = $this->getPersonCrawler($url);

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

        $table->children('tr.item')->each(static function (Crawler $tr) use ($header, $record, &$ids): void {
            $id = $tr->attr('data-id');

            if (in_array($id, $ids, true)) {
                return;
            }

            $ids[] = $id;

            $tds = $tr->filter('td');

            $nameEl = $tds->eq(0);

            if ($nameEl->count() < 1) {
                return;
            }

            $active = str_contains(trim($nameEl->text()), 'Зареєстровано');

            $toRemove = $tds->eq(0)->children()->last()->getNode(0);
            $toRemove->parentNode->removeChild($toRemove);

            $name = trim($tds->eq(0)->text());

            if (empty($name)) {
                return;
            }

            $hrefEl = $tr->filter('a');

            if ($hrefEl->count() > 0) {
                $href = trim($hrefEl->eq(0)->attr('href') ?? '');
                $href = empty($href) ? null : (self::URL . $href);
            }

            if (isset($header[1]) && str_contains($header[1], 'ЄДРПОУ') && $tds->eq(1)->count() > 0) {
                $number = preg_replace('/[^0-9]/', '', trim($tds->eq(1)->text()));
            }

            if (isset($header[2]) && $tds->eq(2)->count() > 0) {
                $address = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && $tds->eq(3)->count() > 0) {
                $type = trim($tds->eq(3)->text());
            }

            $item = new ClarityPersonEdr(
                $name,
                type: empty($type) ? null : $type,
                href: empty($href) ? null : $href,
                number: empty($number) ? null : $number,
                active: $active,
                address: empty($address) ? null : $address
            );

            $record->addItem($item);
        });

        return count($record->getItems()) === 0 ? null : $record;
    }

    private function searchPersonCourtsRecord(string $url): ?ClarityPersonCourtsRecord
    {
        $record = new ClarityPersonCourtsRecord();

        $crawler = $this->getPersonCrawler($url);

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

            if ($tds->count() < 1) {
                return;
            }

            $number = trim($tds->eq(0)->text());

            if (empty($number)) {
                return;
            }

            if (isset($header[1]) && str_contains($header[1], 'Стан') && $tds->eq(1)->count() > 0) {
                $state = trim($tds->eq(1)->text());
            }

            if (isset($header[2]) && str_contains($header[2], 'Сторона') && $tds->eq(2)->count() > 0) {
                $side = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && str_contains($header[3], 'Опис') && $tds->eq(3)->count() > 0) {
                $desc = trim($tds->eq(3)->text());
            }

            if (isset($header[4]) && str_contains($header[4], 'Суд') && $tds->eq(4)->count() > 0) {
                $place = trim($tds->eq(4)->text());
            }

            $item = new ClarityPersonCourt(
                $number,
                state: empty($state) ? null : $state,
                side: empty($side) ? null : $side,
                desc: empty($desc) ? null : $desc,
                place: empty($place) ? null : $place
            );

            $record->addItem($item);
        });

        return count($record->getItems()) === 0 ? null : $record;
    }

    private function searchPersonDebtorsRecord(string $url): ?ClarityPersonDebtorsRecord
    {
        $record = new ClarityPersonDebtorsRecord();

        $crawler = $this->getPersonCrawler($url);

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

            if ($tds->count() < 1) {
                return;
            }

            $name = trim($tds->eq(0)->text());

            if (empty($name)) {
                return;
            }

            if (isset($header[1]) && str_contains($header[1], 'народження') && $tds->eq(1)->count() > 0) {
                $bornAt = trim($tds->eq(1)->text());
                $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt)->setTime(0, 0);
                $bornAt = $bornAt === false ? null : $bornAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Запис') && $tds->eq(2)->count() > 0) {
                $category = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && str_contains($header[3], 'Актуально') && $tds->eq(3)->count() > 0) {
                $actualAt = trim($tds->eq(3)->text());
                $actualAt = empty($actualAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $actualAt)->setTime(0, 0);
                $actualAt = $actualAt === false ? null : $actualAt;
            }

            $item = new ClarityPersonDebtor(
                $name,
                bornAt: empty($bornAt) ? null : $bornAt,
                category: empty($category) ? null : $category,
                actualAt: empty($actualAt) ? null : $actualAt,
            );

            $record->addItem($item);
        });

        return count($record->getItems()) === 0 ? null : $record;
    }

    private function searchPersonSecurityRecord(string $url): ?ClarityPersonSecurityRecord
    {
        $record = new ClarityPersonSecurityRecord();

        $crawler = $this->getPersonCrawler($url);

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

            if ($tds->count() < 1) {
                return;
            }

            $name = trim($tds->eq(0)->text());

            if (empty($name)) {
                return;
            }

            if (isset($header[1]) && str_contains($header[1], 'народження') && $tds->eq(1)->count() > 0) {
                $bornAt = trim($tds->eq(1)->text());
                $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt)->setTime(0, 0);
                $bornAt = $bornAt === false ? null : $bornAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Категорія') && $tds->eq(2)->count() > 0) {
                $category = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && str_contains($header[3], 'Регіон') && $tds->eq(3)->count() > 0) {
                $region = trim($tds->eq(3)->text());
            }

            if (isset($header[4]) && str_contains($header[4], 'зникнення') && $tds->eq(4)->count() > 0) {
                if ($tds->eq(4)->filter('.small')->count() > 0) {
                    $archive = trim($tds->eq(4)->filter('.small')->text());
                    $archive = empty($archive) ? null : str_contains($archive, 'архівна');
                }

                if (!empty($tds->eq(4)->getNode(0)?->firstChild?->textContent)) {
                    $absentAt = trim($tds->eq(4)->getNode(0)->firstChild->textContent ?? '');
                    $absentAt = empty($absentAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $absentAt)->setTime(0, 0);
                    $absentAt = $absentAt === false ? null : $absentAt;
                }
            }

            $accusation = null;
            $precaution = null;

            $nextTr = $tr->nextAll();

            if ($nextTr->matches('.table-collapse-details')) {
                $trs = $nextTr->filter('tr');
                if ($trs->eq(5)->filter('td')->count() > 0 && str_contains($trs->eq(5)->filter('td')->eq(0)->text(), 'Звинувачення')) {
                    $accusation = trim($trs->eq(5)->filter('td')->eq(1)->text());
                }
                if ($trs->eq(6)->filter('td')->count() > 0 && str_contains($trs->eq(6)->filter('td')->eq(0)->text(), 'Запобіжний')) {
                    $precaution = trim($trs->eq(6)->filter('td')->eq(1)->text());
                }
            }

            $item = new ClarityPersonSecurity(
                $name,
                bornAt: empty($bornAt) ? null : $bornAt,
                category: empty($category) ? null : $category,
                region: empty($region) ? null : $region,
                absentAt: empty($absentAt) ? null : $absentAt,
                archive: $archive ?? null,
                accusation: empty($accusation) ? null : $accusation,
                precaution: empty($precaution) ? null : $precaution
            );

            $record->addItem($item);
        });

        return count($record->getItems()) === 0 ? null : $record;
    }

    private function searchPersonEnforcementsRecord(string $url): ?ClarityPersonEnforcementsRecord
    {
        $record = new ClarityPersonEnforcementsRecord();

        $crawler = $this->getPersonCrawler($url);

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

            if ($tds->count() < 1) {
                return;
            }

            $number = trim($tds->eq(0)->text());

            if (empty($number)) {
                return;
            }

            if (isset($header[1]) && str_contains($header[1], 'відкриття') && $tds->eq(1)->count() > 0) {
                $openedAt = trim($tds->eq(1)->text());
                $openedAt = empty($openedAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $openedAt)->setTime(0, 0);
                $openedAt = $openedAt === false ? null : $openedAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Стягувач') && $tds->eq(2)->count() > 0) {
                $collector = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && str_contains($header[3], 'Боржник') && $tds->eq(3)->count() > 0) {
                $debtor = trim($tds->eq(3)->text());

                if ($tds->count() === count($header) + 1) {
                    if ($tds->eq(4)->count() > 0) {
                        $bornAt = trim($tds->eq(4)->text());
                        $peaces = explode(' ', $bornAt);
                        $bornAt = $peaces[count($peaces) - 1];
                        $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt)->setTime(0, 0);
                        $bornAt = $bornAt === false ? null : $bornAt;
                    }
                }
            }

            if (isset($header[4]) && str_contains($header[4], 'Стан') && $tds->eq($tds->count() - 1)->count() > 0) {
                $state = trim($tds->eq($tds->count() - 1)?->text());
            }

            $item = new ClarityPersonEnforcement(
                $number,
                openedAt: empty($openedAt) ? null : $openedAt,
                collector: empty($collector) ? null : $collector,
                debtor: empty($debtor) ? null : $debtor,
                bornAt: empty($bornAt) ? null : $bornAt,
                state: empty($state) ? null : $state
            );

            $record->addItem($item);
        });

        return count($record->getItems()) === 0 ? null : $record;
    }

    private function searchPersonDeclarationsRecord(string $url): ?ClarityPersonDeclarationsRecord
    {
        $record = new ClarityPersonDeclarationsRecord();

        $crawler = $this->getPersonCrawler($url);

        $table = $crawler->filter('[data-id="declarations"] table')->eq(0);
        $header = [];
        $tr = $table->children('tr')->eq(0);
        $tr->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'Рік')) {
            return null;
        }

        if (!isset($header[1]) || !str_contains($header[1], 'ПІБ')) {
            return null;
        }

        if (!isset($header[2]) || !str_contains($header[2], 'Посада')) {
            return null;
        }

        $table->children('tr.item')->each(static function (Crawler $tr) use ($header, $record): void {
            $tds = $tr->filter('td');

            if (isset($header[1]) && $tds->eq(1)->count() > 0) {
                $name = trim($tds->eq(1)->text());

                $hrefEl = $tds->eq(1)->filter('a');

                if ($hrefEl->count() > 0) {
                    $href = trim($hrefEl->eq(0)->attr('href') ?? '');
                    $href = empty($href) ? null : (self::URL . $href);
                }
            }

            if (empty($name)) {
                return;
            }

            if (isset($header[0]) && $tds->count() > 0) {
                $year = trim($tds->eq(0)->text());
            }

            if (isset($header[2]) && $tds->eq(2)->count() > 0) {
                $position = trim($tds->eq(2)->text());
            }

            $item = new ClarityPersonDeclaration(
                $name,
                href: empty($href) ? null : $href,
                year: empty($year) ? null : $year,
                position: empty($position) ? null : $position
            );

            $record->addItem($item);
        });

        return count($record->getItems()) === 0 ? null : $record;
    }

    private function searchEdrsRecord(string $name): ?ClarityEdrsRecord
    {
        $record = new ClarityEdrsRecord();

        $crawler = $this->getEdrsCrawler($name);

        $crawler->filter('.results-wrap .item')->each(static function (Crawler $item) use ($record): void {
            $nameEl = $item->filter('h5');

            if ($nameEl->count() < 1) {
                return;
            }

            $name = trim($nameEl->text());

            if (empty($name)) {
                return;
            }

            $hrefEl = $nameEl->filter('a');

            if ($hrefEl->count() > 0) {
                $href = trim($hrefEl->eq(0)->attr('href') ?? '');
                $href = empty($href) ? null : (self::URL . $href);
            }

            $numberEl = $item->filter('.small');

            if ($numberEl->count() > 0) {
                preg_match('/[0-9]{8}/', $numberEl->text(), $m);
                $number = isset($m, $m[0]) ? $m[0] : null;
            }

            $addressEl = $item->filter('.address');

            if ($addressEl->count() > 0) {
                $address = trim($addressEl->text());
            }

            $item = new ClarityEdr(
                $name,
                type: empty($type) ? null : $type,
                href: empty($href) ? null : $href,
                number: empty($number) ? null : $number,
                active: $active ?? null,
                address: empty($address) ? null : $address
            );

            $record->addItem($item);
        });

        return count($record->getItems()) === 0 ? null : $record;
    }

    private function getPersonsCrawler(string $name): Crawler
    {
        return $this->crawlerProvider->getCrawler('GET', '/persons?search=' . $name, base: self::URL, user: true);
    }

    private function getPersonCrawler(string $url): Crawler
    {
        return $this->crawlerProvider->getCrawler('GET', $url, base: str_starts_with($url, self::URL) ? null : self::URL);
    }

    private function getEdrsCrawler(string $name): Crawler
    {
        return $this->crawlerProvider->getCrawler('GET', '/edrs?search=' . $name, base: self::URL, user: true);
    }
}
