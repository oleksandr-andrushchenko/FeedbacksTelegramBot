<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;
use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;

abstract class TelegramConversation implements TelegramConversationInterface
{
    public function __construct(
        private readonly TelegramAwareHelper $awareHelper,
        protected TelegramConversationState $state,
    )
    {
    }

    abstract protected function invoke(TelegramAwareHelper $tg, Conversation $conversation): null;

    public function invokeConversation(Telegram $telegram, Conversation $conversation): void
    {
        $tg = $this->awareHelper->withTelegram($telegram);

        $this->invoke($tg, $conversation);
    }

    public function getState(): TelegramConversationState
    {
        return $this->state;
    }

    public function setState(TelegramConversationState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function null(): null
    {
        return null;
    }
}