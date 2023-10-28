<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackLookup;
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
     * @param FeedbackLookup $feedbackLookup
     * @param int $limit
     * @return FeedbackSearch[]
     */
    public function searchFeedbackSearches(FeedbackLookup $feedbackLookup, int $limit = 20): array
    {
        $feedbackSearches = $this->repository->createQueryBuilder('fs')
            ->innerJoin('fs.searchTerm', 't')
            ->andWhere('t.normalizedText = :searchTermNormalizedText')
            ->setParameter('searchTermNormalizedText', $feedbackLookup->getSearchTerm()->getNormalizedText())
            ->setMaxResults(100)
            ->getQuery()
            ->getResult()
        ;

        $feedbackSearches = array_filter(
            $feedbackSearches,
            static fn (FeedbackSearch $feedbackSearch): bool => $feedbackLookup->getSearchTerm()->getType() === SearchTermType::unknown
                || $feedbackSearch->getSearchTerm()->getType() === SearchTermType::unknown
                || $feedbackLookup->getSearchTerm()->getType() === $feedbackSearch->getSearchTerm()->getType()
        );

        $feedbackSearches = array_values($feedbackSearches);
        $feedbackSearches = array_reverse($feedbackSearches, true);

        return array_slice($feedbackSearches, 0, $limit, true);
    }
}
