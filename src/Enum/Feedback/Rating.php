<?php

declare(strict_types=1);

namespace App\Enum\Feedback;

enum Rating: int
{
    case extremely_unsatisfied = -3;
    case very_unsatisfied = -2;
    case unsatisfied = -1;
    case neutral = 0;
    case satisfied = 1;
    case very_satisfied = 2;
    case extremely_satisfied = 3;

    public static function random(): self
    {
        return self::cases()[array_rand(self::cases())];
    }
}
