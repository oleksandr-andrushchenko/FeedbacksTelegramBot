<?php

declare(strict_types=1);

namespace App\Service\Feedback\Statistic;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Entity\User\User;
use App\Repository\Feedback\FeedbackLookupRepository;
use App\Service\Feedback\Command\FeedbackCommandStatisticProviderInterface;
use DateTimeImmutable;
use Generator;

class FeedbackLookupStatisticProvider implements FeedbackCommandStatisticProviderInterface
{
    public function __construct(
        private readonly FeedbackCommandOptions $options,
        private readonly FeedbackLookupRepository $repository,
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