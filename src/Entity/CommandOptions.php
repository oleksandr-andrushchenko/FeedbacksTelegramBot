<?php

declare(strict_types=1);

namespace App\Entity;

readonly class CommandOptions
{
    public function __construct(
        private array $limits,
        private bool $shouldLogActivities,
    )
    {
    }

    /**
     * @return CommandLimit[]
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
