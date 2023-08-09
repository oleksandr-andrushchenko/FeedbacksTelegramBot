<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

readonly class TelegramOptions
{
    public function __construct(
        private array $localeCodes,
        private int $adminId,
    )
    {
    }

    public function getLocaleCodes(): array
    {
        return $this->localeCodes;
    }

    public function getAdminId(): int
    {
        return $this->adminId;
    }
}
