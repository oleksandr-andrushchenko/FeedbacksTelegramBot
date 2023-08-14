<?php

declare(strict_types=1);

namespace App\Exception\Telegram\Api;

use App\Exception\Telegram\TelegramException;
use Throwable;

class InvalidCurrencyTelegramException extends TelegramException
{
    public function __construct(private readonly string $currency, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Invalid "%s" telegram bot currency', $currency), $code, $previous);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}