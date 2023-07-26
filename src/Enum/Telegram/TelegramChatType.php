<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramChatType: int
{
    case private = 0;
    case group = 1;
    case supergroup = 2;
    case channel = 3;

    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $enum) {
            if ($enum->name === $name) {
                return $enum;
            }
        }

        return null;
    }
}