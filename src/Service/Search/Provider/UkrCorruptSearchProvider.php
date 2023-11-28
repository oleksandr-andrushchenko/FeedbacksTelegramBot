<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrCorrupt\UkrCorruptPerson;
use App\Entity\Search\UkrCorrupt\UkrCorruptPersons;
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
    private const URL = 'https://corruptinfo.nazk.gov.ua/ep/1.0/corrupt/findData';

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
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        if ($type !== SearchTermType::person_name) {
            return false;
        }

        if (count(explode(' ', $term)) === 1) {
            return false;
        }

        if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $term) !== 1) {
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

    public function searchPersons(string $name): ?UkrCorruptPersons
    {
        $words = array_map('trim', explode(' ', $name));
        $count = count($words);

        $bodies = [];

        if ($count === 3) {
            $bodies[] = [
                'indLastNameOnOffenseMoment' => $words[0],
                'indFirstNameOnOffenseMoment' => $words[1],
                'indPatronymicOnOffenseMoment' => $words[2],
            ];
            $bodies[] = [
                'indLastNameOnOffenseMoment' => $words[1],
                'indFirstNameOnOffenseMoment' => $words[0],
                'indPatronymicOnOffenseMoment' => $words[2],
            ];
        } elseif ($count == 2) {
            $bodies[] = [
                'indLastNameOnOffenseMoment' => $words[0],
                'indFirstNameOnOffenseMoment' => $words[1],
            ];
            $bodies[] = [
                'indLastNameOnOffenseMoment' => $words[1],
                'indFirstNameOnOffenseMoment' => $words[0],
            ];
        }

        $records = [];

        foreach ($bodies as $body) {
            $data = $this->httpRequester->requestHttp('POST', self::URL, json: $body, array: true);

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
                    sentenceDate: isset($item['sentenceDate']) ? DateTimeImmutable::createFromFormat('Y-m-d', $item['sentenceDate'])->setTime(0, 0) : null,
                    punishmentStart: isset($item['punishmentStart']) ? DateTimeImmutable::createFromFormat('Y-m-d', $item['punishmentStart'])->setTime(0, 0) : null,
                    courtName: $item['courtName'] ?? null,
                    codexArticles: isset($item['codexArticles']) ? array_map(fn (array $article) => $article['codexArticleName'], $item['codexArticles']) : null
                );
            }

            if (count($records) > 0) {
                break;
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

        return count($records) === 0 ? null : new UkrCorruptPersons(array_values($records));
    }
}
