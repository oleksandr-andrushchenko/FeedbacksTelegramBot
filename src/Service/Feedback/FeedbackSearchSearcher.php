<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchSearch;
use App\Enum\Feedback\SearchTermType;
use App\Repository\Feedback\FeedbackSearchRepository;

class FeedbackSearchSearcher
{
    public function __construct(
        private readonly FeedbackSearchRepository $repository,
    )
    {
    }

    /**
     * @param FeedbackSearchSearch $feedbackSearchSearch
     * @param int $limit
     * @return FeedbackSearch[]
     */
    public function searchFeedbackSearches(FeedbackSearchSearch $feedbackSearchSearch, int $limit = 20): array
    {
        $feedbackSearches = $this->repository->createQueryBuilder('fs')
            ->andWhere('fs.searchTermNormalizedText = :searchTermNormalizedText')
            ->setParameter('searchTermNormalizedText', $feedbackSearchSearch->getSearchTermNormalizedText())
            ->setMaxResults(100)
            ->getQuery()
            ->getResult()
        ;

        $feedbackSearches = array_filter($feedbackSearches, function (FeedbackSearch $feedbackSearch) use ($feedbackSearchSearch) {
            if (
                $feedbackSearchSearch->getSearchTermType() !== SearchTermType::unknown
                && $feedbackSearch->getSearchTermType() !== SearchTermType::unknown
                && $feedbackSearchSearch->getSearchTermType() !== $feedbackSearch->getSearchTermType()
            ) {
                return false;
            }

            if (
                $feedbackSearchSearch->getSearchTermMessenger() !== null
                && $feedbackSearchSearch->getSearchTermMessenger() !== $feedbackSearch->getSearchTermMessenger()
            ) {
                return false;
            }

            return true;
        });

        $feedbackSearches = array_values($feedbackSearches);
        $feedbackSearches = array_reverse($feedbackSearches, true);

        return array_slice($feedbackSearches, 0, $limit, true);
    }
}
