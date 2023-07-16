<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\Api\TelegramMessageSenderInterface;
use PHPUnit\Framework\MockObject\MockObject;

trait TelegramMessageSenderMockProviderTrait
{
    public function getTelegramMessageSenderMock(bool $replace = true): TelegramMessageSenderInterface|MockObject
    {
        $mock = $this->createMock(TelegramMessageSenderInterface::class);

        if ($replace) {
            static::getContainer()->set('app.telegram_message_sender', $mock);
        }

        return $mock;
    }
}