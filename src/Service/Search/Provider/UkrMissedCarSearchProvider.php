<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\UkrMissedCar\UkrMissedCar;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\CrawlerProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://wanted.mvs.gov.ua/searchtransport/?NOM=%D0%90%D0%900601%D0%92%D0%A2&NSH=&g-recaptcha-response=
 */
class UkrMissedCarSearchProvider implements SearchProviderInterface
{
    private const URL = 'https://wanted.mvs.gov.ua/searchtransport';

    public function __construct(
        private readonly CrawlerProvider $crawlerProvider,
    )
    {
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::ukr_missed_cars;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        $type = $searchTerm->getType();

        if ($type !== SearchTermType::car_number) {
            return false;
        }

        return true;
    }

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array
    {
        $term = $searchTerm->getNormalizedText();

        return [
            $this->searchMissedCars($term),
        ];
    }

    private function getMissedCarsCrawler(string $number): Crawler
    {
        return $this->crawlerProvider->getCrawler('GET', self::URL, query: ['NOM' => $number], user: true);
    }

    private function searchMissedCars(string $number): array
    {
        $records = [];

        $crawler = $this->getMissedCarsCrawler($number);

        $crawler->filter('.cards-list > .card > .info-list')->each(static function (Crawler $item) use (&$records): void {
            $listItems = $item->filter('.info-list-item');

            if ($listItems->count() === 0) {
                return;
            }

            $map = [
                'Регіон' => 'region',
                'Державний знак' => 'carNumber',
                'МОДЕЛЬ' => 'model',
                'Номер шасі' => 'chassisNumber',
                'Номер кузова' => 'bodyNumber',
                'Колір' => 'color',
            ];
            $values = array_combine(array_values($map), array_fill(0, count($map), null));

            $listItems->each(static function (Crawler $listItem) use ($map, &$values): void {
                $divs = $listItem->children('div');

                if ($divs->count() < 2) {
                    return;
                }

                $labelEl = $divs->eq(0);
                $valueEl = $divs->eq(1);

                foreach ($map as $label => $key) {
                    if (str_contains($labelEl->text(), $label)) {
                        $values[$key] = $valueEl->text();
                    }
                }
            });

            $carNumber = $values['carNumber'];

            if (empty($carNumber)) {
                return;
            }

            $records[] = new UkrMissedCar(
                $carNumber,
                region: empty($values['region']) ? null : $values['region'],
                model: empty($values['model']) ? null : $values['model'],
                chassisNumber: empty($values['chassisNumber']) ? null : $values['chassisNumber'],
                bodyNumber: empty($values['bodyNumber']) ? null : $values['bodyNumber'],
                color: empty($values['color']) ? null : $values['color'],
            );
        });

        return $records;
    }
}
