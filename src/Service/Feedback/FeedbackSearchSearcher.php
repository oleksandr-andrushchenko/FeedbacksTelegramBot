<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Repository\Feedback\FeedbackSearchRepository;

class FeedbackSearchSearcher
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
    )
    {
    }

    /**
     * @param FeedbackSearchTerm $feedbackSearchTerm
     * @param int $maxResults
     * @return FeedbackSearch[]
     */
    public function searchFeedbackSearches(FeedbackSearchTerm $feedbackSearchTerm, int $maxResults = 20): array
    {
        $feedbackSearches = $this->feedbackSearchRepository->findByNormalizedText(
            $feedbackSearchTerm->getNormalizedText(),
            maxResults: $maxResults
        );

        $feedbackSearches = array_filter(
            $feedbackSearches,
            static fn (FeedbackSearch $feedbackSearch): bool => $feedbackSearchTerm->getType() === SearchTermType::unknown
                || $feedbackSearch->getSearchTerm()->getType() === SearchTermType::unknown
                || $feedbackSearchTerm->getType() === $feedbackSearch->getSearchTerm()->getType()
        );

        $feedbackSearches = array_values($feedbackSearches);
        $feedbackSearches = array_reverse($feedbackSearches, true);

        return array_slice($feedbackSearches, 0, $maxResults, true);
    }
}
