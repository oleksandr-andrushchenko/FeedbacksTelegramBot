<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramKeyboardFactory;

trait TelegramKeyboardFactoryProviderTrait
{
    public function getTelegramKeyboardFactory(): TelegramKeyboardFactory
    {
        return static::getContainer()->get('app.telegram_keyboard_factory');
    }
}