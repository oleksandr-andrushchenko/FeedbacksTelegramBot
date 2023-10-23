<?php

declare(strict_types=1);

namespace App\Service\Feedback\Statistic;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Entity\User\User;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\Feedback\Command\FeedbackCommandStatisticProviderInterface;
use DateTimeImmutable;
use Generator;

class FeedbackStatisticProvider implements FeedbackCommandStatisticProviderInterface
{
    public function __construct(
        private readonly FeedbackCommandOptions $options,
        private readonly FeedbackRepository $repository,
    )
    {
    }

    public function getStatistics(User $user): Generator
    {
        foreach ($this->getLimits() as $limit) {
            $count = $this->repository
                ->createQueryBuilder('f')
                ->select('COUNT(f.id)')
                ->andWhere('f.createdAt >= :createdAtFrom')
                ->setParameter('createdAtFrom', new DateTimeImmutable(sprintf('-1 %s', $limit->getPeriod())))
                ->andWhere('f.user = :user')
                ->setParameter('user', $user)
                ->andWhere('f.hasActiveSubscription = :hasActiveSubscription')
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