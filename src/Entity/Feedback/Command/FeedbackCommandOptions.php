<?php

declare(strict_types=1);

namespace App\Entity\Feedback\Command;

readonly class FeedbackCommandOptions
{
    public function __construct(
        private array $limits,
        private bool $shouldLogActivities,
    )
    {
    }

    /**
     * @return FeedbackCommandLimit[]
     */
    public function getLimits(): array
    {
        return $this->limits;
    }

    public function shouldLogActivities(): bool
    {
        return $this->shouldLogActivities;
    }
}
