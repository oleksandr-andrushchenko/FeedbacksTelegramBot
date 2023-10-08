<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Enum\Telegram\TelegramGroup;
use DateTimeImmutable;
use DateTimeInterface;

class TelegramBot
{
    public function __construct(
        private readonly string $username,
        private TelegramGroup $group,
        private string $name,
        private string $token,
        private string $countryCode,
        private string $localeCode,
        private bool $checkUpdates = true,
        private bool $checkRequests = true,
        private bool $acceptPayments = false,
        private array $adminIds = [],
        private bool $adminOnly = true,
        private bool $textsSet = false,
        private bool $webhookSet = false,
        private bool $commandsSet = false,
        private bool $primary = true,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?DateTimeInterface $updatedAt = null,
        private ?DateTimeInterface $deletedAt = null,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(string $localeCode): self
    {
        $this->localeCode = $localeCode;

        return $this;
    }

    public function getGroup(): TelegramGroup
    {
        return $this->group;
    }

    public function setGroup(TelegramGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function checkUpdates(): bool
    {
        return $this->checkUpdates;
    }

    public function setCheckUpdates(bool $checkUpdates): self
    {
        $this->checkUpdates = $checkUpdates;

        return $this;
    }

    public function checkRequests(): bool
    {
        return $this->checkRequests;
    }

    public function setCheckRequests(bool $checkRequests): self
    {
        $this->checkRequests = $checkRequests;

        return $this;
    }

    public function acceptPayments(): bool
    {
        return $this->acceptPayments;
    }

    public function setAcceptPayments(bool $acceptPayments): self
    {
        $this->acceptPayments = $acceptPayments;

        return $this;
    }

    public function getAdminIds(): array
    {
        return array_map(fn ($adminId) => (int) $adminId, $this->adminIds);
    }

    public function setAdminIds(array $adminIds): self
    {
        foreach ($adminIds as $adminId) {
            $this->addAdminId($adminId);
        }

        return $this;
    }

    public function addAdminId(string|int $adminId): self
    {
        $this->adminIds[] = (int) $adminId;
        $this->adminIds = array_filter(array_unique($this->adminIds));

        return $this;
    }

    public function adminOnly(): bool
    {
        return $this->adminOnly;
    }

    public function setAdminOnly(bool $adminOnly): self
    {
        $this->adminOnly = $adminOnly;

        return $this;
    }

    public function textsSet(): bool
    {
        return $this->textsSet;
    }

    public function setTextsSet(bool $textsSet): self
    {
        $this->textsSet = $textsSet;

        return $this;
    }

    public function webhookSet(): bool
    {
        return $this->webhookSet;
    }

    public function setWebhookSet(bool $webhookSet): self
    {
        $this->webhookSet = $webhookSet;

        return $this;
    }

    public function commandsSet(): bool
    {
        return $this->commandsSet;
    }

    public function setCommandsSet(bool $commandsSet): self
    {
        $this->commandsSet = $commandsSet;

        return $this;
    }

    public function primary(): bool
    {
        return $this->primary;
    }

    public function setPrimary(bool $primary): self
    {
        $this->primary = $primary;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }
}
