<?php

declare(strict_types=1);

namespace App\Enum\Lookup;

enum LookupProcessorName: string
{
    case feedbacks = 'feedbacks';
    case clarity = 'clarity';
    case searches = 'searches';
}
