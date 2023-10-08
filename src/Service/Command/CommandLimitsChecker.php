<?php

declare(strict_types=1);

namespace App\Service\Command;

use App\Entity\User\User;
use App\Exception\CommandLimitExceededException;

class CommandLimitsChecker
{
    public function __construct(
        private readonly bool $checkLimits,
    )
    {
    }

    /**
     * @param User $user
     * @param CommandStatisticProviderInterface $statisticProvider
     * @return void
     * @throws CommandLimitExceededException
     */
    public function checkCommandLimits(User $user, CommandStatisticProviderInterface $statisticProvider): void
    {
        if (!$this->checkLimits) {
            return;
        }

        $statistics = $statisticProvider->getStatistics($user);
        $limits = $statisticProvider->getLimits();

        foreach ($statistics as $period => $current) {
            $count = null;

            foreach ($limits as $limit) {
                if ($limit->getPeriod() === $period) {
                    $count = $limit->getCount();
                    break;
                }
            }

            if ($count === null) {
                continue;
            }

            if ($current >= $count) {
                throw new CommandLimitExceededException($limit);
            }
        }
    }
}