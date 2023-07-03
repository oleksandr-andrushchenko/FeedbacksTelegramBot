<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramUserProvider;

trait TelegramUserProviderTrait
{
    public function getTelegramUserProvider(): TelegramUserProvider
    {
        return static::getContainer()->get('app.telegram_user_provider');
    }
}