<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

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
}