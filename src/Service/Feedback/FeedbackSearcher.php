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
            ->innerJoin('f.searchTerms', 't')
            ->andWhere('t.normalizedText = :searchTermNormalizedText')
            ->setParameter('searchTermNormalizedText', $feedbackSearch->getSearchTerm()->getNormalizedText())
            ->setMaxResults(100)
            ->getQuery()
            ->getResult()
        ;

        // todo: if search term type is unknown - need to make multi-searches with normalized search term type for each possible type
        // todo: for example: search term=+1 (561) 314-5672, its a phone number, stored as: 15613145672, but search with unknown type will give FALSE (+1 (561) 314-5672 === 15613145672)
        // todo: coz it wasnt parsed to selected seearch term type

        $feedbacks = array_filter($feedbacks, static function (Feedback $feedback) use ($feedbackSearch): bool {
            foreach ($feedback->getSearchTerms() as $searchTerm) {
                if ($searchTerm->getNormalizedText() === $feedbackSearch->getSearchTerm()->getNormalizedText()) {
                    if (
                        $feedbackSearch->getSearchTerm()->getType() !== SearchTermType::unknown
                        && $searchTerm->getType() !== SearchTermType::unknown
                        && $feedbackSearch->getSearchTerm()->getType() !== $searchTerm->getType()
                    ) {
                        return false;
                    }

                    if (
                        $feedbackSearch->getSearchTerm()->getMessenger() !== null
                        && $feedbackSearch->getSearchTerm()->getMessenger() !== $searchTerm->getMessenger()
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

        return array_slice($feedbacks, 0, $limit, true);
    }
}
