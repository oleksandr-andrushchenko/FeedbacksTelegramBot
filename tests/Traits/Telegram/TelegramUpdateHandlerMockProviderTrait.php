<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramUpdateHandler;
use PHPUnit\Framework\MockObject\MockObject;

trait TelegramUpdateHandlerMockProviderTrait
{
    public function getTelegramUpdateHandlerMock(bool $replace = true): TelegramUpdateHandler|MockObject
    {
        $telegramWebhookRequestUpdateMock = $this->createMock(TelegramUpdateHandler::class);

        if ($replace) {
            static::getContainer()->set('app.telegram_update_handler', $telegramWebhookRequestUpdateMock);
        }

        return $telegramWebhookRequestUpdateMock;
    }
}