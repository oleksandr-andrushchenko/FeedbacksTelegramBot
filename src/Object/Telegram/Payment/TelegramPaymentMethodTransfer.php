<?php

declare(strict_types=1);

namespace App\Object\Telegram\Payment;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Telegram\TelegramPaymentMethodName;

readonly class TelegramPaymentMethodTransfer
{

    public function __construct(
        private TelegramBot $bot,
        private TelegramPaymentMethodName $name,
        private string $token,
        private array $currencies,
    )
    {
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

    public function getCurrencies(): array
    {
        return $this->currencies;
    }
}