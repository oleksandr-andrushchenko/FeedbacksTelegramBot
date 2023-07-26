<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramPayment;
use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramConversationFactory;

abstract class TelegramChannel implements TelegramChannelInterface
{
    public function __construct(
        private readonly TelegramAwareHelper $awareHelper,
        protected readonly TelegramConversationFactory $conversationFactory,
    )
    {
    }

    abstract protected function getCommands(TelegramAwareHelper $tg): iterable;

    public function getTelegramCommands(Telegram $telegram): iterable
    {
        $tg = $this->awareHelper->withTelegram($telegram);

        return $this->getCommands($tg);
    }

    public function getTelegramConversationFactory(): TelegramConversationFactory
    {
        return $this->conversationFactory;
    }

    abstract protected function acceptPayment(TelegramPayment $payment, TelegramAwareHelper $tg): void;

    public function acceptTelegramPayment(Telegram $telegram, TelegramPayment $payment): void
    {
        $tg = $this->awareHelper->withTelegram($telegram);
        $this->acceptPayment($payment, $tg);
    }
}