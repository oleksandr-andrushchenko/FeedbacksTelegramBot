<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramMessengerUserFinderType: string
{
    case PERSISTED = 'persisted';
    case BOT_API = 'bot-api';
}