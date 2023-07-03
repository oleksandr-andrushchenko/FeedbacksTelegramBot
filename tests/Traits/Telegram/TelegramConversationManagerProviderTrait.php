<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramConversationManager;

trait TelegramConversationManagerProviderTrait
{
    public function getTelegramConversationManager(): TelegramConversationManager
    {
        return static::getContainer()->get('app.telegram_conversation_manager');
    }
}