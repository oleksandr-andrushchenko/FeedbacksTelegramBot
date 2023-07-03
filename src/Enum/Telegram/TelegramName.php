<?php

declare(strict_types=1);

namespace App\Enum\Telegram;

enum TelegramName: int
{
    case default = 0;
    case feedbacks = 1;

    public static function fromName(string $value): self
    {
        return match ($value) {
            'default' => self::default,
            'feedbacks' => self::feedbacks,
        };
    }
}