<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrMissed\UkrMissedPerson;
use App\Entity\Search\UkrMissed\DisappearedPersonsUkrMissedRecord;
use App\Entity\Search\UkrMissed\WantedPersonsUkrMissedRecord;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\HttpRequester;
use DateTimeImmutable;

/**
 * @see https://www.npu.gov.ua/rozshuk-zniklih-gromadyan?&surname=%D0%90%D0%BD%D0%B4%D1%80%D1%83%D1%89%D0%B5%D0%BD%D0%BA%D0%BE&apiType=0
 * @see https://www.npu.gov.ua/api/integration/disappeared-persons-by-constituent-data?&surname=%D0%90%D0%9D%D0%94%D0%A0%D0%A3%D0%A9%D0%95%D0%9D%D0%9A%D0%9E&apiType=0
 * @see https://www.npu.gov.ua/api/integration/wanted-persons-by-constituent-data?&surname=%D0%90%D0%BD%D0%B4%D1%80%D1%83%D1%89%D0%B5%D0%BD%D0%BA%D0%BE&apiType=1&page=1
 */
class UkrMissedSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private readonly HttpRequester $httpRequester,
    )
    {
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::ukr_missed;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        if ($this->supportsPersonName($searchTerm->getType(), $searchTerm->getNormalizedText(), $context)) {
            return true;
        }

        return false;
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

    public function getSearcher(FeedbackSearchTerm $searchTerm, array $context = []): ?callable
    {
        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        if ($this->supportsPersonName($type, $term, $context)) {
            return fn (): array => [
                $this->searchDisappearedPersons($term),
                $this->searchWantedPersons($term),
            ];
        }

        return null;
    }

    public function searchDisappearedPersons(string $name): ?DisappearedPersonsUkrMissedRecord
    {
        $url = 'https://www.npu.gov.ua/api/integration/disappeared-persons-by-constituent-data';
        $disappeared = true;
        $persons = $this->searchPersons($name, $url, $disappeared);

        if ($persons === null) {
            return null;
        }

        return new DisappearedPersonsUkrMissedRecord($persons);
    }

    public function searchWantedPersons(string $name): ?WantedPersonsUkrMissedRecord
    {
        $url = 'https://www.npu.gov.ua/api/integration/wanted-persons-by-constituent-data';
        $disappeared = false;
        $persons = $this->searchPersons($name, $url, $disappeared);

        if ($persons === null) {
            return null;
        }

        return new WantedPersonsUkrMissedRecord($persons);
    }

    public function searchPersons(string $name, string $url, bool $disappeared): ?array
    {
        $words = array_map('trim', explode(' ', $name));
        $count = count($words);

        $queryVariants = [];

        if ($count === 3) {
            $queryVariants[] = [
                'surname' => $words[0],
                'name' => $words[1],
                'middlename' => $words[2],
            ];
            $queryVariants[] = [
                'surname' => $words[1],
                'name' => $words[0],
                'middlename' => $words[2],
            ];
        } elseif ($count == 2) {
            $queryVariants[] = [
                'surname' => $words[0],
                'name' => $words[1],
            ];
            $queryVariants[] = [
                'surname' => $words[1],
                'name' => $words[0],
            ];
        }

        $records = [];

        foreach ($queryVariants as $queryVariant) {
            $queryVariant = array_merge($queryVariant, [
                'apiType' => '0',
                'page' => '1',
            ]);

            $data = $this->httpRequester->requestHttp('GET', $url, query: $queryVariant, array: true);

            foreach ($data['items'] as $item) {
                var_dump($item);
                $records[] = new UkrMissedPerson(
                    surname: isset($item['person'], $item['person']['surname']) ? $item['person']['surname'] : null,
                    name: isset($item['person'], $item['person']['name']) ? $item['person']['name'] : null,
                    middleName: isset($item['person'], $item['person']['middlename']) ? $item['person']['middlename'] : null,
                    sex: isset($item['person'], $item['person']['sex']) ? $item['person']['sex'] : null,
                    birthday: isset($item['person'], $item['person']['birthday']) ? DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $item['person']['birthday']) : null,
                    photo: isset($item['person'], $item['person']['photo']) ? $item['person']['photo'] : null,
                    category: isset($item['wanted'], $item['wanted']['category']) ? $item['wanted']['category'] : null,
                    disappeared: $disappeared,
                    articles: isset($item['wanted'], $item['wanted']['articles']) ? $item['wanted']['articles'] : null,
                    date: isset($item['wanted'], $item['wanted']['datetime']) ? DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $item['wanted']['datetime']) : null,
                    organ: isset($item['wanted'], $item['wanted']['organ']) ? $item['wanted']['organ'] : null,
                    precaution: isset($item['wanted'], $item['wanted']['precaution']) ? $item['wanted']['precaution'] : null,
                    address: implode(', ', array_filter([
                        isset($item['address'], $item['address']['country']) ? $item['address']['country'] : null,
                        isset($item['address'], $item['address']['region']) ? $item['address']['region'] : null,
                        isset($item['address'], $item['address']['district']) ? $item['address']['district'] : null,
                        isset($item['address'], $item['address']['locality']) ? $item['address']['locality'] : null,
                    ])),
                );
            }
        }

        return count($records) === 0 ? null : $records;
    }
}
