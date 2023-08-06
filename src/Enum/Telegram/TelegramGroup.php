<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramGroup: int
{
    case feedbacks = 1;

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