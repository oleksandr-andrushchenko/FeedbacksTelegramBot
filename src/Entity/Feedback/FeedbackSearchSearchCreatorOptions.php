<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

readonly class FeedbackSearchSearchCreatorOptions
{
    public function __construct(
        private bool $logActivities,
    )
    {
    }

    public function logActivities(): bool
    {
        return $this->logActivities;
    }
}
