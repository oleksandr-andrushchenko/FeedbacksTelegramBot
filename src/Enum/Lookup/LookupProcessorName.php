<?php

declare(strict_types=1);

namespace App\Enum\Lookup;

enum LookupProcessorName: string
{
    case feedbacks = 'feedbacks';

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
