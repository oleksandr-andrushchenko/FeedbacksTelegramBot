<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use Closure;

interface TelegramBotCommandInterface
{
    public function getCallback(): Closure;
}
