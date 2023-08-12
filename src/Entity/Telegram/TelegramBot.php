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
        private readonly string $token,
        private readonly string $countryCode,
        private readonly string $localeCode,
        private readonly TelegramGroup $group,
        private readonly ?TelegramBot $primaryBot = null,
        private bool $isCheckUpdates = true,
        private bool $isCheckRequests = true,
        private bool $isAcceptPayments = false,
        private bool $isAdminOnly = true,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?int $id = null,
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }

    public function getGroup(): TelegramGroup
    {
        return $this->group;
    }

    public function getPrimaryBot(): ?TelegramBot
    {
        return $this->primaryBot;
    }

    public function checkUpdates(): bool
    {
        return $this->isCheckUpdates;
    }

    public function setIsCheckUpdates(bool $isCheckUpdates): self
    {
        $this->isCheckUpdates = $isCheckUpdates;

        return $this;
    }

    public function checkRequests(): bool
    {
        return $this->isCheckRequests;
    }

    public function setIsCheckRequests(bool $isCheckRequests): self
    {
        $this->isCheckRequests = $isCheckRequests;

        return $this;
    }

    public function acceptPayments(): bool
    {
        return $this->isAcceptPayments;
    }

    public function setIsAcceptPayments(bool $isAcceptPayments): self
    {
        $this->isAcceptPayments = $isAcceptPayments;

        return $this;
    }

    public function adminOnly(): bool
    {
        return $this->isAdminOnly;
    }

    public function setIsAdminOnly(bool $isAdminOnly): self
    {
        $this->isAdminOnly = $isAdminOnly;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
