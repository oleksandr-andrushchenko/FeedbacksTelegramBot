<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramChatType: int
{
    case private = 0;
    case group = 1;
    case supergroup = 2;
    case channel = 3;

    public static function fromString(string $value): self
    {
        return match ($value) {
            'private' => self::private,
            'group' => self::group,
            'supergroup' => self::supergroup,
            'channel' => self::channel,
        };
    }
}