<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Tests\Fake\Service\Telegram\Api\FakeTelegramMessageSender;

trait TelegramMessageSenderProviderTrait
{
    public function getTelegramMessageSender(): FakeTelegramMessageSender
    {
        return static::getContainer()->get('app.telegram_message_sender');
    }
}