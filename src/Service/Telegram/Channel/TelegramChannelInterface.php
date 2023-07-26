<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramPayment;
use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramCommandInterface;
use App\Service\Telegram\TelegramConversationFactory;

interface TelegramChannelInterface
{
    /**
     * @return TelegramCommandInterface[]
     */
    public function getTelegramCommands(Telegram $telegram): iterable;

    public function getTelegramConversationFactory(): TelegramConversationFactory;

    public function acceptTelegramPayment(Telegram $telegram, TelegramPayment $payment): void;
}