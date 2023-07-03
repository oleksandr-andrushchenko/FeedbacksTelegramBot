<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;
use App\Service\Telegram\Telegram;
use App\Entity\Telegram\TelegramConversation as Conversation;

interface TelegramConversationInterface
{
    public function invokeConversation(Telegram $telegram, Conversation $conversation): void;

    public function getState(): TelegramConversationState;

    public function setState(TelegramConversationState $state): static;
}