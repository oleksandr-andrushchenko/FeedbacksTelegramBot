<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Enum\Telegram\TelegramPaymentMethodName;

readonly class TelegramPaymentMethod
{
    public function __construct(
        private TelegramPaymentMethodName $name,
        private string $token,
        private string $currency,
        private array $countries,
        private ?string $flag,
    )
    {
    }

    public function getName(): TelegramPaymentMethodName
    {
        return $this->name;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCountries(): array
    {
        return $this->countries;
    }

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function isGlobal(): bool
    {
        return count($this->countries) === 0;
    }
}
