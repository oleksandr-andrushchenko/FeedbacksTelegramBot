<?php

declare(strict_types=1);

namespace App\Enum\Search;

enum SearchProviderName: string
{
    case feedbacks = 'feedbacks';
    case clarity = 'clarity';
    case searches = 'searches';
    case ukraine_corrupts = 'ukraine_corrupts';
    case ukr_missed = 'ukr_missed';
}
