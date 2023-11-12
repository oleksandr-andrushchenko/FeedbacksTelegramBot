<?php

declare(strict_types=1);

namespace App\Enum\Lookup;

enum LookupProcessorName: int
{
    case feedbacks_registry = 0;
    case ukraine_court_decisions_registry = 1;

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
