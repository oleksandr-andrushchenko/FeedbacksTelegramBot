<?php

declare(strict_types=1);

namespace App\Enum\Lookup;

enum LookupProcessorName: int
{
    case feedbacks_registry = 0;
    case ukraine_court_decisions_registry = 1;
}
