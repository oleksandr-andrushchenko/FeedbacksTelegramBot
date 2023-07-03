<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramConversation;
use App\Entity\Telegram\TelegramConversationState;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;

interface TelegramConversationCommandInterface
{
    public static function getConversationStateClass(): string;

    /**
     * @param Telegram $telegram
     * @param Update $update
     * @param TelegramConversation $conversation
     * @param TelegramConversationState $state
     * @return ServerResponse
     */
    public function invokeConversation(
        Telegram $telegram,
        Update $update,
        TelegramConversation $conversation,
        TelegramConversationState $state
    ): ServerResponse;
}