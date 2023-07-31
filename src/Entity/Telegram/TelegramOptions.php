<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

readonly class TelegramOptions
{
    public function __construct(
        private string $apiToken,
        private string $username,
        private string $webhookUrl,
        private string $webhookCertificatePath,
        private array $languageCodes,
        private array $adminIds,
        private array $adminChatIds,
        private bool $checkUpdates,
        private bool $checkRequests,
        private bool $processAdminOnly,
        private bool $acceptPayments,
    )
    {
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    public function getWebhookCertificatePath(): string
    {
        return $this->webhookCertificatePath;
    }

    public function getLanguageCodes(): array
    {
        return $this->languageCodes;
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
}
