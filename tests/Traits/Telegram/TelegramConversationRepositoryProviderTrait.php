<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Repository\Telegram\TelegramConversationRepository;

trait TelegramConversationRepositoryProviderTrait
{
    public function getTelegramConversationRepository(): TelegramConversationRepository
    {
        return static::getContainer()->get('app.repository.telegram_conversation');
    }
}