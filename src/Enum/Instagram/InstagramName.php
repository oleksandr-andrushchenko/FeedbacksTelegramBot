<?php

declare(strict_types=1);

namespace App\Enum\Instagram;

enum InstagramName: int
{
    case default = 0;
    case feedbacks = 1;

    public static function fromString(string $value): self
    {
        return match ($value) {
            'default' => self::default,
            'feedbacks' => self::feedbacks,
        };
    }
}