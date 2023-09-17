<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Repository\Telegram\TelegramStoppedConversationRepository;

trait TelegramStoppedConversationRepositoryProviderTrait
{
    public function getTelegramStoppedConversationRepository(): TelegramStoppedConversationRepository
    {
        return static::getContainer()->get('app.repository.telegram_stopped_conversation');
    }
}