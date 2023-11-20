<?php

declare(strict_types=1);

namespace App\Enum\Search;

enum SearchProviderName: int
{
    case feedbacks = 0;
    case clarity = 1;
    case searches = 2;
    case ukr_corrupts = 3;
    case ukr_missed = 4;
    case otzyvua = 5;

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
