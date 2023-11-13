<?php

declare(strict_types=1);

namespace App\Enum\Lookup;

enum LookupProcessorName: string
{
    case feedbacks = 'feedbacks';
    case clarity = 'clarity';

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
