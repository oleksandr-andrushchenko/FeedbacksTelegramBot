<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Repository\Feedback\FeedbackRepository;

class FeedbackSearcher
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
    )
    {
    }

    /**
     * @param FeedbackSearchTerm $feedbackSearchTerm
     * @param bool $withUsers
     * @param int $maxResults
     * @return Feedback[]
     */
    public function searchFeedbacks(FeedbackSearchTerm $feedbackSearchTerm, bool $withUsers = false, int $maxResults = 20): array
    {
        $feedbacks = $this->feedbackRepository->findByNormalizedText(
            $feedbackSearchTerm->getNormalizedText(),
            withUsers: $withUsers,
            maxResults: $maxResults
        );

        // todo: if search term type is unknown - need to make multi-searches with normalized search term type for each possible type
        // todo: for example: search term=+1 (561) 314-5672, its a phone number, stored as: 15613145672, but search with unknown type will give FALSE (+1 (561) 314-5672 === 15613145672)
        // todo: coz it wasnt parsed to selected seearch term type

        $feedbacks = array_filter($feedbacks, static function (Feedback $feedback) use ($feedbackSearchTerm): bool {
            foreach ($feedback->getSearchTerms() as $searchTerm) {
                if (strcmp(mb_strtolower($searchTerm->getNormalizedText()), mb_strtolower($feedbackSearchTerm->getNormalizedText())) === 0) {
                    if (
                        $feedbackSearchTerm->getType() !== SearchTermType::unknown
                        && $searchTerm->getType() !== SearchTermType::unknown
                        && $feedbackSearchTerm->getType() !== $searchTerm->getType()
                    ) {
                        return false;
                    }

                    return true;
                }
            }

            return false;
        });

        $feedbacks = array_values($feedbacks);
        $feedbacks = array_reverse($feedbacks, true);

        return array_slice($feedbacks, 0, $maxResults, true);
    }
}
