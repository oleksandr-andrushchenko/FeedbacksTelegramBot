<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\User\User;
use App\Repository\Feedback\FeedbackSearchRepository;
use Generator;
use DateTime;

class UserCreateFeedbackSearchStatisticsProvider
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
    )
    {
    }

    public function getUserCreateFeedbackSearchStatistics(array $periods, User $user): Generator
    {
        foreach ($periods as $period) {
            yield $period => $this->getStatistics($user, sprintf('-1 %s', $period));
        }
    }

    private function getStatistics(User $user, string $dateTimeModifier): ?int
    {
        $count = $this->feedbackSearchRepository
            ->createQueryBuilder('fs')
            ->select('COUNT(fs.id)')
            ->andWhere('fs.createdAt >= :createdAtFrom')
            ->setParameter('createdAtFrom', (new DateTime())->modify($dateTimeModifier))
            ->andWhere('fs.user = :user')
            ->setParameter('user', $user)
            ->andWhere('fs.isPremium = :isPremium')
            ->setParameter('isPremium', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if (is_string($count)) {
            return (int) $count;
        }

        return $count;
    }
}