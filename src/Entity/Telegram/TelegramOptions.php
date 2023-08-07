<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

readonly class TelegramOptions
{
    public function __construct(
        private string $groupKey,
        private string $apiToken,
        private string $username,
        private array $localeCodes,
        private int $adminId,
        private bool $checkUpdates,
        private bool $checkRequests,
        private bool $processAdminOnly,
        private bool $acceptPayments,
    )
    {
    }

    public function getGroupKey(): string
    {
        return $this->groupKey;
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getLocaleCodes(): array
    {
        return $this->localeCodes;
    }

    public function getAdminId(): int
    {
        return $this->adminId;
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
}
