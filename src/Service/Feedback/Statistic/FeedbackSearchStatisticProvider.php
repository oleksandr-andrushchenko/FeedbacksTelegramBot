<?php

declare(strict_types=1);

namespace App\Service\Feedback\Statistic;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Entity\User\User;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Service\Feedback\Command\FeedbackCommandStatisticProviderInterface;
use DateTimeImmutable;
use Generator;

class FeedbackSearchStatisticProvider implements FeedbackCommandStatisticProviderInterface
{
    public function __construct(
        private readonly FeedbackCommandOptions $options,
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