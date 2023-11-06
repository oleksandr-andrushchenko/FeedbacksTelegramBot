<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Repository\Feedback\FeedbackLookupRepository;

class FeedbackLookupSearcher
{
    public function __construct(
        private readonly FeedbackLookupRepository $feedbackLookupRepository,
    )
    {
    }

    /**
     * @param FeedbackSearchTerm $feedbackSearchTerm
     * @param int $maxResults
     * @return FeedbackLookup[]
     */
    public function searchFeedbackLookups(FeedbackSearchTerm $feedbackSearchTerm, int $maxResults = 20): array
    {
        $feedbackLookups = $this->feedbackLookupRepository->findByNormalizedText(
            $feedbackSearchTerm->getNormalizedText(),
            maxResults: $maxResults
        );

        $feedbackLookups = array_filter(
            $feedbackLookups,
            static fn (FeedbackLookup $feedbackLookup): bool => $feedbackSearchTerm->getType() === SearchTermType::unknown
                || $feedbackLookup->getSearchTerm()->getType() === SearchTermType::unknown
                || $feedbackSearchTerm->getType() === $feedbackLookup->getSearchTerm()->getType()
        );

        $feedbackLookups = array_values($feedbackLookups);
        $feedbackLookups = array_reverse($feedbackLookups, true);

        return array_slice($feedbackLookups, 0, $maxResults, true);
    }
}
