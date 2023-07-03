<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramConversationStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case STOPPED = 'stopped';
    case FINISHED = 'finished';
}