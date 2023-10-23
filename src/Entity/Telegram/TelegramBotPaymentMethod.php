<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Enum\Telegram\TelegramBotPaymentMethodName;
use DateTimeImmutable;
use DateTimeInterface;

class TelegramBotPaymentMethod
{
    public function __construct(
        private readonly TelegramBot $bot,
        private readonly TelegramBotPaymentMethodName $name,
        private readonly string $token,
        private readonly array $currencyCodes,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?DateTimeInterface $deletedAt = null,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
    }

    public function getName(): TelegramBotPaymentMethodName
    {
        return $this->name;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCurrencyCodes(): array
    {
        return $this->currencyCodes;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
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