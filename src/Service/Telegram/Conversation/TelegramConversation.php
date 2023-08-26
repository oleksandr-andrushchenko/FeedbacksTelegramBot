<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;

abstract class TelegramConversation implements TelegramConversationInterface
{
    public function __construct(
        protected TelegramConversationState $state,
    )
    {
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
}