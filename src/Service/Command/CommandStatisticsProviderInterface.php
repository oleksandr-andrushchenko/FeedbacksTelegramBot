<?php

declare(strict_types=1);

namespace App\Service\Command;

use App\Entity\CommandLimit;
use App\Entity\User\User;
use Generator;

interface CommandStatisticsProviderInterface
{
    public function getStatistics(User $user): Generator;

    /**
     * @return CommandLimit[]
     */
    public function getLimits(): array;
}