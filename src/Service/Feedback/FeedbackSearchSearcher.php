<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackLookup;
use App\Enum\Feedback\SearchTermType;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;

class FeedbackSearchSearcher
{
    public function __construct(
        private readonly FeedbackSearchRepository $repository,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
    )
    {
    }

    /**
     * @param FeedbackLookup $feedbackSearchSearch
     * @param int $limit
     * @return FeedbackSearch[]
     */
    public function searchFeedbackSearches(FeedbackLookup $feedbackSearchSearch, int $limit = 20): array
    {
        $feedbackSearches = $this->repository->createQueryBuilder('fs')
            ->innerJoin('fs.searchTerm', 't')
            ->andWhere('t.normalizedText = :searchTermNormalizedText')
            ->setParameter('searchTermNormalizedText', $feedbackSearchSearch->getSearchTerm()->getNormalizedText())
            ->setMaxResults(100)
            ->getQuery()
            ->getResult()
        ;

        $feedbackSearches = array_filter($feedbackSearches, function (FeedbackSearch $feedbackSearch) use ($feedbackSearchSearch) {
            if (
                $feedbackSearchSearch->getSearchTerm()->getType() !== SearchTermType::unknown
                && $feedbackSearch->getSearchTerm()->getType() !== SearchTermType::unknown
                && $feedbackSearchSearch->getSearchTerm()->getType() !== $feedbackSearch->getSearchTerm()->getType()
            ) {
                return false;
            }

            $lookupMessenger = $this->searchTermMessengerProvider->getSearchTermMessenger(
                $feedbackSearchSearch->getSearchTerm()->getType()
            );
            $searchMessenger = $this->searchTermMessengerProvider->getSearchTermMessenger(
                $feedbackSearch->getSearchTerm()->getType()
            );

            if ($lookupMessenger !== null && $lookupMessenger !== $searchMessenger) {
                return false;
            }

            return true;
        });

        $feedbackSearches = array_values($feedbackSearches);
        $feedbackSearches = array_reverse($feedbackSearches, true);

        return array_slice($feedbackSearches, 0, $limit, true);
    }
}
