<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Closure;

interface TelegramCommandInterface
{
    public function getCallback(): Closure;
}
