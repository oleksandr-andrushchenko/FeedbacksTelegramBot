<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrCorrupt\UkrCorruptPerson;
use App\Entity\Search\UkrCorrupt\UkrCorruptPersonsRecord;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\HttpRequester;
use DateTimeImmutable;

/**
 * @see https://corruptinfo.nazk.gov.ua/
 * @see https://corruptinfo.nazk.gov.ua/ep/swagger/ui/index
 */
class UkrCorruptSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private readonly HttpRequester $httpRequester,
    )
    {
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::ukr_corrupts;
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

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array
    {
        $term = $searchTerm->getNormalizedText();

        return [
            $this->searchPersons($term),
        ];
    }

    public function searchPersons(string $name): ?UkrCorruptPersonsRecord
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
                $records[] = new UkrCorruptPerson(
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

        $records = array_filter($records, static function (UkrCorruptPerson $record) use ($words): bool {
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

        return count($records) === 0 ? null : new UkrCorruptPersonsRecord($records);
    }
}
