<?php

declare(strict_types=1);

namespace App\Enum\Instagram;

enum InstagramName: int
{
    case default = 0;
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