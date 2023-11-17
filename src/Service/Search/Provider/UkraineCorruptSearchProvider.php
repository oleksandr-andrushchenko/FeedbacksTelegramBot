<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkraineCorrupt\UkraineCorruptPerson;
use App\Entity\Search\UkraineCorrupt\UkraineCorruptPersonsRecord;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\HttpRequester;
use DateTimeImmutable;

/**
 * @see https://corruptinfo.nazk.gov.ua/ep/swagger/ui/index
 */
class UkraineCorruptSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private readonly string $environment,
        private readonly HttpRequester $httpRequester,
    )
    {
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::ukraine_corrupts;
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

        if (count(explode(' ', $searchTerm->getNormalizedText())) === 1) {
            return false;
        }

        if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $searchTerm->getNormalizedText()) !== 1) {
            return false;
        }

        return true;
    }

    private function supportsOrganizationName(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        return false;

        if ($this->environment === 'test') {
            return false;
        }

        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        if ($searchTerm->getType() === SearchTermType::organization_name) {
            if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $searchTerm->getNormalizedText()) !== 1) {
                return false;
            }

            return true;
        }

        if ($searchTerm->getType() === SearchTermType::tax_number) {
            if (strlen($searchTerm->getNormalizedText()) !== 8) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function getSearchers(FeedbackSearchTerm $searchTerm, array $context = []): iterable
    {
        if ($this->supportsPersonName($searchTerm, $context)) {
            yield fn () => [$this->searchPersons($searchTerm->getNormalizedText())];
        }

        if ($this->supportsOrganizationName($searchTerm, $context)) {
            if ($searchTerm->getType() === SearchTermType::organization_name) {
                yield fn () => [$this->searchOrganizationsByName($searchTerm->getNormalizedText())];
            } else {
                yield fn () => [$this->searchOrganizationsByCode($searchTerm->getNormalizedText())];
            }
        }

        yield from [];
    }

    public function searchPersons(string $name): ?UkraineCorruptPersonsRecord
    {
        $words = array_map('trim', explode(' ', $name));
        $count = count($words);

        $bodyVariants = [];

        if ($count === 3) {
            $bodyVariants[] = [
                'indLastNameOnOffenseMoment' => $words[0],
                'indFirstNameOnOffenseMoment' => $words[1],
                'indPatronymicOnOffenseMoment' => $words[2],
            ];
            $bodyVariants[] = [
                'indLastNameOnOffenseMoment' => $words[1],
                'indFirstNameOnOffenseMoment' => $words[0],
                'indPatronymicOnOffenseMoment' => $words[2],
            ];
        } elseif ($count == 2) {
            $bodyVariants[] = [
                'indLastNameOnOffenseMoment' => $words[0],
                'indFirstNameOnOffenseMoment' => $words[1],
            ];
            $bodyVariants[] = [
                'indLastNameOnOffenseMoment' => $words[1],
                'indFirstNameOnOffenseMoment' => $words[0],
            ];
        }

        $records = [];

        foreach ($bodyVariants as $bodyVariant) {
            $url = 'https://corruptinfo.nazk.gov.ua/ep/1.0/corrupt/findData';
            $data = $this->httpRequester->requestHttp('POST', $url, json: $bodyVariant, array: true);

            foreach ($data as $item) {
                $records[] = new UkraineCorruptPerson(
                    punishmentType: isset($item['punishmentType'], $item['punishmentType']['name']) ? $item['punishmentType']['name'] : null,
                    entityType: isset($item['entityType'], $item['entityType']['name']) ? $item['entityType']['name'] : null,
                    lastName: $item['indLastNameOnOffenseMoment'] ?? null,
                    firstName: $item['indFirstNameOnOffenseMoment'] ?? null,
                    patronymic: $item['indPatronymicOnOffenseMoment'] ?? null,
                    offenseName: $item['offenseName'] ?? null,
                    punishment: $item['punishment'] ?? null,
                    courtCaseNumber: $item['courtCaseNumber'] ?? null,
                    sentenceDate: isset($item['sentenceDate']) ? DateTimeImmutable::createFromFormat('Y-m-d', $item['sentenceDate']) : null,
                    punishmentStart: isset($item['punishmentStart']) ? DateTimeImmutable::createFromFormat('Y-m-d', $item['punishmentStart']) : null,
                    courtName: $item['courtName'] ?? null,
                    codexArticles: isset($item['codexArticles']) ? array_map(fn (array $article) => $article['codexArticleName'], $item['codexArticles']) : null
                );
            }
        }

        $records = array_filter($records, static function (UkraineCorruptPerson $record) use ($words): bool {
            foreach ($words as $word) {
                if (str_contains($record->getLastName(), $word)) {
                    continue;
                }

                if (str_contains($record->getFirstName(), $word)) {
                    continue;
                }

                if (str_contains($record->getPatronymic(), $word)) {
                    continue;
                }

                return false;
            }

            return true;
        });

        return count($records) === 0 ? null : new UkraineCorruptPersonsRecord($records);
    }

    public function searchOrganizationsByName(string $name): iterable
    {

    }

    public function searchOrganizationsByCode(string $code): iterable
    {

    }
}
