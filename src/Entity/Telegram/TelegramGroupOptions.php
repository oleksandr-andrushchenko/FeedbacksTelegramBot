<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

readonly class TelegramGroupOptions
{
    public function __construct(
        private string $key,
        private array $bots,
        private array $localeCodes,
        private array $adminIds,
        private array $adminChatIds,
        private bool $checkUpdates,
        private bool $checkRequests,
        private bool $processAdminOnly,
        private bool $acceptPayments,
    )
    {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getBots(): array
    {
        return $this->bots;
    }

    public function getLocaleCodes(): array
    {
        return $this->localeCodes;
    }

    public function getAdminIds(): array
    {
        return $this->adminIds;
    }

    public function getAdminChatIds(): array
    {
        return $this->adminChatIds;
    }

    public function checkUpdates(): bool
    {
        return $this->checkUpdates;
    }

    public function checkRequests(): bool
    {
        return $this->checkRequests;
    }

    public function processAdminOnly(): bool
    {
        return $this->processAdminOnly;
    }

    public function acceptPayments(): bool
    {
        return $this->acceptPayments;
    }

    public function hasBot(string $username): bool
    {
        return array_key_exists($username, $this->bots);
    }
}
