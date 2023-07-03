<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramChatProvider;

trait TelegramChatProviderTrait
{
    public function getTelegramChatProvider(): TelegramChatProvider
    {
        return static::getContainer()->get('app.telegram_chat_provider');
    }
}