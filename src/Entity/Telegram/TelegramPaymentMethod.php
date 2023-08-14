<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Enum\Telegram\TelegramPaymentMethodName;
use DateTimeImmutable;
use DateTimeInterface;

class TelegramPaymentMethod
{
    public function __construct(
        private readonly TelegramBot $bot,
        private readonly TelegramPaymentMethodName $name,
        private readonly string $token,
        private readonly array $currencyCodes,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
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

    public function getName(): TelegramPaymentMethodName
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
}
