<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramChatActionSender;
use PHPUnit\Framework\MockObject\MockObject;

trait TelegramChatActionSenderMockProviderTrait
{
    public function getTelegramChatActionSenderMock(bool $replace = true): TelegramChatActionSender|MockObject
    {
        $mock = $this->createMock(TelegramChatActionSender::class);

        if ($replace) {
            static::getContainer()->set('app.telegram_chat_action_sender', $mock);
        }

        return $mock;
    }
}