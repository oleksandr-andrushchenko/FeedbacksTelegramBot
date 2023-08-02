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
    public function searchFeedbacks(FeedbackSearch $feedbackSearch, int $limit = 100): array
    {
        $feedbacks = $this->feedbackRepository->createQueryBuilder('f')
            ->andWhere('f.searchTermNormalizedText = :searchTermNormalizedText')
            ->setParameter('searchTermNormalizedText', $feedbackSearch->getSearchTermNormalizedText())
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        $feedbacks = array_values(array_filter($feedbacks, function (Feedback $feedback) use ($feedbackSearch) {
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
        }));

//        order by requester country
        $countryCode = $feedbackSearch->getCountryCode();

        usort($feedbacks, fn (Feedback $a, Feedback $b) => match (true) {
            $a->getCountryCode() === $countryCode && $b->getCountryCode() !== $countryCode => 1,
            $a->getCountryCode() !== $countryCode && $b->getCountryCode() === $countryCode => -1,
            default => 0,
        });

        return $feedbacks;
    }
}
