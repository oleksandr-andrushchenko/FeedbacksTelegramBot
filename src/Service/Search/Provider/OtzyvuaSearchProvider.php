<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Otzyvua\OtzyvuaFeedback;
use App\Entity\Search\Otzyvua\OtzyvuaFeedbackSearchTerm;
use App\Entity\Search\Otzyvua\OtzyvuaFeedbackSearchTermsRecord;
use App\Entity\Search\Otzyvua\OtzyvuaFeedbacksRecord;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\CrawlerProvider;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://www.otzyvua.net/uk/privat-bank.html
 * @see https://www.otzyvua.net/ajax/?mode=search&q=%D0%BF%D1%80%D0%B8%D0%B2%D0%B0%D1%82
 * @see https://www.otzyvua.net/search/?q=380965137629
 */
class OtzyvuaSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private readonly CrawlerProvider $crawlerProvider,
    )
    {
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::otzyvua;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        $type = $searchTerm->getType();

        if (in_array($type, SearchTermType::messengers, true)) {
            return false;
        }

        return true;
    }

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array
    {
        $term = $searchTerm->getNormalizedText();
        $record = $this->searchFeedbackSearchTermsRecord($term, sortByLength: true);

        if ($record === null) {
            return [];
        }

        if (count($record->getItems()) === 1) {
            $url = $record->getItems()[0]->getHref();
        } elseif (strcmp(mb_strtolower($term), mb_strtolower($record->getItems()[0]->getName())) === 0) {
            $url = $record->getItems()[0]->getHref();
        }

        if (isset($url)) {
            return [
                $this->searchFeedbacksRecord($url),
            ];
        }

        return [
            $record,
        ];
    }

    private function getFeedbackSearchTermsCrawler(string $name): Crawler
    {
        return $this->crawlerProvider->getCrawler('https://www.otzyvua.net/uk/search/?q=' . $name);
    }

    private function searchFeedbackSearchTermsRecord(string $name, bool $sortByLength = false): ?OtzyvuaFeedbackSearchTermsRecord
    {
        $record = new OtzyvuaFeedbackSearchTermsRecord();

        $crawler = $this->getFeedbackSearchTermsCrawler($name);

        $crawler->filter('#container .otzyv_box_float .row')->each(static function (Crawler $item) use ($record): void {
            $col = $item->filter('.col')->last();
            $nameEl = $col->filter('h2 a');

            if ($nameEl->count() < 1) {
                return;
            }

            $name = trim($nameEl->eq(0)->text() ?? '');
            $href = trim($nameEl->eq(0)->attr('href') ?? '');

            if (empty($name)) {
                return;
            }

            $categoryEl = $col->filter('.h2_descr');

            if ($categoryEl->count() > 0) {
                $category = trim($categoryEl->eq(0)->text() ?? '');
            }

            $ratingEl = $col->filter('.rtng_val');

            if ($ratingEl->count() > 0) {
                $rating = trim($ratingEl->eq(0)->text() ?? '');
                $rating = empty($rating) ? null : (float) $rating;
            }

            $countEl = $col->filter('.num_rev');

            if ($countEl->count() > 0) {
                $count = trim(preg_replace('/[^0-9]/', '', $countEl->eq(0)->text()) ?? '');
                $count = empty($count) ? null : (int) $count;
            }

            $item = new OtzyvuaFeedbackSearchTerm(
                $name,
                $href,
                category: $category ?? null,
                rating: $rating ?? null,
                count: $count ?? null
            );

            $record->addItem($item);
        });

        if ($sortByLength) {
            $items = $record->getItems();
            usort($items, static fn (OtzyvuaFeedbackSearchTerm $a, OtzyvuaFeedbackSearchTerm $b): int => mb_strlen($a->getName()) <=> mb_strlen($b->getName()));
            $record->setItems($items);
        }

        return count($record->getItems()) === 0 ? null : $record;
    }

    private function getFeedbacksCrawler(string $uri): Crawler
    {
        return $this->crawlerProvider->getCrawler($uri);
    }

    private function searchFeedbacksRecord(string $uri): ?OtzyvuaFeedbacksRecord
    {
        $record = new OtzyvuaFeedbacksRecord();

        $crawler = $this->getFeedbacksCrawler($uri);

        $crawler->filter('#comments_container .commentbox')->each(static function (Crawler $item) use ($record): void {
            $row = $item->filter('.comment_row')->first();
            $titleEl = $row->filter('h2 a');

            if ($titleEl->count() < 1) {
                return;
            }

            $title = trim($titleEl->eq(0)->text() ?? '');
            $href = trim($titleEl->eq(0)->attr('href') ?? '');

            if (empty($title)) {
                return;
            }

            $ratingEl = $row->filter('.star_ring span');

            if ($ratingEl->count() > 0) {
                $width = preg_replace('/[^0-9]/', '', trim($ratingEl->eq(0)->attr('style') ?? ''));
                $width = empty($width) ? null : (int) $width;
                $rating = empty($width) ? null : (int) ($width / 13);
            }

            $authorEl = $row->filter('.author_name ins');

            if ($authorEl->count() > 0) {
                $authorName = trim($authorEl->eq(0)->text() ?? '');

                if ($authorEl->filter('a')->count() > 0) {
                    $authorHref = trim($authorEl->filter('a')->eq(0)->attr('href') ?? '');
                }
            }

            $createdEl = $row->filter('.dtreviewed .value-title');

            if ($createdEl->count() > 0) {
                $createdAt = trim($createdEl->eq(0)->attr('title') ?? '');
                $createdAt = empty($createdAt) ? null : DateTimeImmutable::createFromFormat('Y-m-d', $createdAt);
                $createdAt = $createdAt === false ? null : $createdAt;
            }

            $descEl = $row->filter('.comment.description');

            if ($descEl->count() > 0) {
                if ($descEl->filter('.review-full-text')->count() > 0) {
                    $description = trim($descEl->filter('.review-full-text')->text() ?? '');
                } elseif ($descEl->filter('.review-snippet')->count() > 0) {
                    $description = trim($descEl->filter('.review-snippet')->text() ?? '');
                }
            }

            $item = new OtzyvuaFeedback(
                $title,
                $href,
                rating: $rating ?? null,
                authorName: $authorName ?? null,
                authorHref: $authorHref ?? null,
                description: $description ?? null,
                createdAt: $createdAt ?? null,
            );

            $record->addItem($item);
        });

        return count($record->getItems()) === 0 ? null : $record;
    }
}
