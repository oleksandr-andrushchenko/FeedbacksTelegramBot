<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

readonly class TelegramRequestLimits
{
    public function __construct(
        private int $perSecondAll,
        private int $perSecond,
        private int $perMinute,
    )
    {
    }

    public function getPerSecondAll(): int
    {
        return $this->perSecondAll;
    }

    public function getPerSecond(): int
    {
        return $this->perSecond;
    }

    public function getPerMinute(): int
    {
        return $this->perMinute;
    }
}
