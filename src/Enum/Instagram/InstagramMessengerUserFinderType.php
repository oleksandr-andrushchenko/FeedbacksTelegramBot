<?php

declare(strict_types=1);

namespace App\Enum\Instagram;

enum InstagramMessengerUserFinderType: string
{
    case PERSISTED = 'persisted';
    case OPEN_QUERY = 'open-query';
}