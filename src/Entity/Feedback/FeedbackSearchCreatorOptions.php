<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

readonly class FeedbackSearchCreatorOptions
{
    public function __construct(
        private int $userPerDayLimit,
        private int $userPerMonthLimit,
        private int $userPerYearLimit,
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

    public function getLimits(): array
    {
        return [
            'day' => $this->userPerDayLimit(),
            'month' => $this->userPerMonthLimit(),
            'year' => $this->userPerYearLimit(),
        ];
    }
}
