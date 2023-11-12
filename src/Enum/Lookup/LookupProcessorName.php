<?php

declare(strict_types=1);

namespace App\Enum\Lookup;

enum LookupProcessorName: int
{
    case feedbacks_registry = 0;

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
