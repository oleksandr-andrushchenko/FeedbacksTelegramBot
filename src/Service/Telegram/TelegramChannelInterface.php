<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramPayment;

interface TelegramChannelInterface
{
    /**
     * @return TelegramCommandInterface[]
     */
    public function getTelegramCommands(Telegram $telegram): iterable;

    public function getTelegramConversationFactory(): TelegramConversationFactory;

    public function acceptTelegramPayment(Telegram $telegram, TelegramPayment $payment): void;
}