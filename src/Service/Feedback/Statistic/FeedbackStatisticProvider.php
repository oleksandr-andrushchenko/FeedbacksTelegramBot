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
        private readonly FeedbackCommandOptions $feedbackCommandOptions,
        private readonly FeedbackRepository $feedbackRepository,
    )
    {
    }

    public function getStatistics(User $user): Generator
    {
        foreach ($this->getLimits() as $limit) {
            yield $limit->getPeriod() => $this->feedbackRepository
                ->countByUserAndFromWithoutActiveSubscription(
                    $user,
                    new DateTimeImmutable(sprintf('-1 %s', $limit->getPeriod()))
                )
            ;
        }
    }

    public function getLimits(): array
    {
        return $this->feedbackCommandOptions->getLimits();
    }
}