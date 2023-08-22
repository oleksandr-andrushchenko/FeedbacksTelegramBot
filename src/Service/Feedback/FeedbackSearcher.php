<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearch;
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
     * @param FeedbackSearch $feedbackSearch
     * @param int $limit
     * @return Feedback[]
     */
    public function searchFeedbacks(FeedbackSearch $feedbackSearch, int $limit = 20): array
    {
        $feedbacks = $this->feedbackRepository->createQueryBuilder('f')
            ->andWhere('f.searchTermNormalizedText = :searchTermNormalizedText')
            ->setParameter('searchTermNormalizedText', $feedbackSearch->getSearchTermNormalizedText())
            ->setMaxResults(100)
            ->getQuery()
            ->getResult()
        ;

        $feedbacks = array_filter($feedbacks, function (Feedback $feedback) use ($feedbackSearch) {
            if (
                $feedbackSearch->getSearchTermType() !== SearchTermType::unknown
                && $feedback->getSearchTermType() !== SearchTermType::unknown
                && $feedbackSearch->getSearchTermType() !== $feedback->getSearchTermType()
            ) {
                return false;
            }

            if (
                $feedbackSearch->getSearchTermMessenger() !== null
                && $feedbackSearch->getSearchTermMessenger() !== $feedback->getSearchTermMessenger()
            ) {
                return false;
            }

            return true;
        });

        $feedbacks = array_values($feedbacks);
        $feedbacks = array_reverse($feedbacks, true);

        return array_slice($feedbacks, 0, $limit, true);
    }
}
