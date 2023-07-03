<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Enum\Messenger\Messenger;
use App\Exception\Messenger\NotSupportedMessengerException;

class MessengerConnectionLinkMaker
{
    public function makeMessengerConnectionLink(Messenger $messenger, string $handler = null): string
    {
        return match ($messenger) {
            Messenger::instagram => '',
            Messenger::telegram => '',
            default => throw new NotSupportedMessengerException(),
        };
    }
}