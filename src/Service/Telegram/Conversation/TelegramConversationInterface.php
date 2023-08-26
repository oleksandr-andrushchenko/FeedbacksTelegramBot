<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversation as Entity;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Telegram\TelegramAwareHelper;

interface TelegramConversationInterface
{
    public function getState(): TelegramConversationState;

    public function setState(TelegramConversationState $state): static;

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null;
}