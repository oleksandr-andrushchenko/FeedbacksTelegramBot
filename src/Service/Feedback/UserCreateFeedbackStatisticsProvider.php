<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\User\User;
use App\Repository\Feedback\FeedbackRepository;
use Generator;
use DateTime;

class UserCreateFeedbackStatisticsProvider
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
    )
    {
    }

    public function getUserCreateFeedbackStatistics(array $periods, User $user): Generator
    {
        foreach ($periods as $period) {
            yield $period => $this->getStatistics($user, sprintf('-1 %s', $period));
        }
    }

    private function getStatistics(User $user, string $dateTimeModifier): ?int
    {
        $count = $this->feedbackRepository
            ->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.createdAt >= :createdAtFrom')
            ->setParameter('createdAtFrom', (new DateTime())->modify($dateTimeModifier))
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->andWhere('f.hasActiveSubscription = :hasActiveSubscription')
            ->setParameter('hasActiveSubscription', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if (is_string($count)) {
            return (int) $count;
        }

        return $count;
    }
}