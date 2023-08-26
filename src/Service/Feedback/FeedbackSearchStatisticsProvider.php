<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\CommandOptions;
use App\Entity\User\User;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Service\Command\CommandStatisticsProviderInterface;
use DateTimeImmutable;
use Generator;

class FeedbackSearchStatisticsProvider implements CommandStatisticsProviderInterface
{
    public function __construct(
        private readonly CommandOptions $options,
        private readonly FeedbackSearchRepository $repository,
    )
    {
    }

    public function getStatistics(User $user): Generator
    {
        foreach ($this->getLimits() as $limit) {
            $count = $this->repository
                ->createQueryBuilder('fs')
                ->select('COUNT(fs.id)')
                ->andWhere('fs.createdAt >= :createdAtFrom')
                ->setParameter('createdAtFrom', new DateTimeImmutable(sprintf('-1 %s', $limit->getPeriod())))
                ->andWhere('fs.user = :user')
                ->setParameter('user', $user)
                ->andWhere('fs.hasActiveSubscription = :hasActiveSubscription')
                ->setParameter('hasActiveSubscription', false)
                ->getQuery()
                ->getSingleScalarResult()
            ;

            yield $limit->getPeriod() => is_string($count) ? (int) $count : $count;
        }
    }

    public function getLimits(): array
    {
        return $this->options->getLimits();
    }
}