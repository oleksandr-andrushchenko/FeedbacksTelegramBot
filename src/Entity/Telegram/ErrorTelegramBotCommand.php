<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use Closure;

readonly class ErrorTelegramBotCommand implements TelegramBotCommandInterface
{
    public function __construct(
        private Closure $callback,
    )
    {
    }

    public function getCallback(): Closure
    {
        return $this->callback;
    }
}