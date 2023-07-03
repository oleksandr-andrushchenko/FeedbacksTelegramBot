<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Service\Telegram\TelegramAwareHelper;

class DefaultTelegramChannel extends TelegramChannel implements TelegramChannelInterface
{
    protected function getCommands(TelegramAwareHelper $tg): iterable
    {
        yield from [];
    }
}