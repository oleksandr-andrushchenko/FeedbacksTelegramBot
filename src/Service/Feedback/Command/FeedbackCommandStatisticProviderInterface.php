<?php

declare(strict_types=1);

namespace App\Service\Feedback\Command;

use App\Entity\Feedback\Command\FeedbackCommandLimit;
use App\Entity\User\User;
use Generator;

interface FeedbackCommandStatisticProviderInterface
{
    public function getStatistics(User $user): Generator;

    /**
     * @return FeedbackCommandLimit[]
     */
    public function getLimits(): array;
}