<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Closure;

readonly class FallbackTelegramCommand implements TelegramCommandInterface
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