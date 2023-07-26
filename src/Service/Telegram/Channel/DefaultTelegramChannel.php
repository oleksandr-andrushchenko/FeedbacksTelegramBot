<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramPayment;
use App\Service\Telegram\TelegramAwareHelper;

class DefaultTelegramChannel extends TelegramChannel implements TelegramChannelInterface
{
    protected function getCommands(TelegramAwareHelper $tg): iterable
    {
        yield from [];
    }

    protected function acceptPayment(TelegramPayment $payment, TelegramAwareHelper $tg): void
    {
    }
}