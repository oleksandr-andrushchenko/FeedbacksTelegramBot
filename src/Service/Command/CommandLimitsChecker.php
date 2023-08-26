<?php

declare(strict_types=1);

namespace App\Service\Command;

use App\Entity\User\User;
use App\Exception\CommandLimitExceeded;

class CommandLimitsChecker
{
    /**
     * @param User $user
     * @param CommandStatisticsProviderInterface $statisticsProvider
     * @return void
     * @throws CommandLimitExceeded
     */
    public function checkCommandLimits(User $user, CommandStatisticsProviderInterface $statisticsProvider): void
    {
        $statistics = $statisticsProvider->getStatistics($user);
        $limits = $statisticsProvider->getLimits();

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
                throw new CommandLimitExceeded($limit);
            }
        }
    }
}