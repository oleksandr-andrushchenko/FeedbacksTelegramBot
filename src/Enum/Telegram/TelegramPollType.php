<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramPollType: int
{
    case regular = 0;
    case quiz = 1;
}