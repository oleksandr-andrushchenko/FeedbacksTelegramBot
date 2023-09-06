<?php

declare(strict_types=1);

namespace App\Service\Feedback\Statistic;

use App\Entity\CommandOptions;
use App\Entity\User\User;
use App\Repository\Feedback\FeedbackSearchSearchRepository;
use App\Service\Command\CommandStatisticProviderInterface;
use DateTimeImmutable;
use Generator;

class FeedbackSearchSearchStatisticProvider implements CommandStatisticProviderInterface
{
    public function __construct(
        private readonly CommandOptions $options,
        private readonly FeedbackSearchSearchRepository $repository,
    )
    {
    }

    public function getStatistics(User $user): Generator
    {
        foreach ($this->getLimits() as $limit) {
            $count = $this->repository
                ->createQueryBuilder('fss')
                ->select('COUNT(fss.id)')
                ->andWhere('fss.createdAt >= :createdAtFrom')
                ->setParameter('createdAtFrom', new DateTimeImmutable(sprintf('-1 %s', $limit->getPeriod())))
                ->andWhere('fss.user = :user')
                ->setParameter('user', $user)
                ->andWhere('fss.hasActiveSubscription = :hasActiveSubscription')
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