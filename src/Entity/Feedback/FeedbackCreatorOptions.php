<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

readonly class FeedbackCreatorOptions
{
    public function __construct(
        private int $userPerDayLimit,
        private int $userPerMonthLimit,
        private int $userPerYearLimit,
        private bool $logActivities,
    )
    {
    }

    public function userPerDayLimit(): int
    {
        return $this->userPerDayLimit;
    }

    public function userPerMonthLimit(): int
    {
        return $this->userPerMonthLimit;
    }

    public function userPerYearLimit(): int
    {
        return $this->userPerYearLimit;
    }

    public function logActivities(): bool
    {
        return $this->logActivities;
    }
}
