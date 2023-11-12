<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramBotGroupName: int
{
    case default = 0;
    case feedbacks = 1;
    case lookups = 2;

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