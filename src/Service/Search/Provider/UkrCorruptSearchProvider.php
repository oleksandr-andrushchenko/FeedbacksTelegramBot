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
class UkrCorruptSearchProvider extends SearchProvider implements SearchProviderInterface
{
    private const URL = 'https://corruptinfo.nazk.gov.ua/ep/1.0/corrupt/findData';

    public function __construct(
        SearchProviderHelper $searchProviderHelper,
        private readonly HttpRequester $httpRequester,
    )
    {
        parent::__construct($searchProviderHelper);
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

        $items = [];

        foreach ($bodies as $body) {
            $rows = $this->httpRequester->requestHttp('POST', self::URL, json: $body, array: true);

            foreach ($rows as $row) {
                $items[] = new UkrCorruptPerson(
                    punishmentType: isset($row['punishmentType'], $row['punishmentType']['name']) ? $row['punishmentType']['name'] : null,
                    entityType: isset($row['entityType'], $row['entityType']['name']) ? $row['entityType']['name'] : null,
                    lastName: $row['indLastNameOnOffenseMoment'] ?? null,
                    firstName: $row['indFirstNameOnOffenseMoment'] ?? null,
                    patronymic: $row['indPatronymicOnOffenseMoment'] ?? null,
                    offenseName: $row['offenseName'] ?? null,
                    punishment: $row['punishment'] ?? null,
                    courtCaseNumber: $row['courtCaseNumber'] ?? null,
                    sentenceDate: isset($row['sentenceDate']) ? DateTimeImmutable::createFromFormat('Y-m-d', $row['sentenceDate'])->setTime(0, 0) : null,
                    punishmentStart: isset($row['punishmentStart']) ? DateTimeImmutable::createFromFormat('Y-m-d', $row['punishmentStart'])->setTime(0, 0) : null,
                    courtName: $row['courtName'] ?? null,
                    codexArticles: isset($row['codexArticles']) ? array_map(fn (array $article) => $article['codexArticleName'], $row['codexArticles']) : null
                );
            }

            if (count($items) > 0) {
                break;
            }
        }

        $items = array_filter($items, static function (UkrCorruptPerson $item) use ($words): bool {
            foreach ($words as $word) {
                if (str_contains($item->getLastName(), $word)) {
                    continue;
                }

                if (str_contains($item->getFirstName(), $word)) {
                    continue;
                }

                if (str_contains($item->getPatronymic(), $word)) {
                    continue;
                }

                return false;
            }

            return true;
        });

        return count($items) === 0 ? null : new UkrCorruptPersons(array_values($items));
    }
}
