<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramUpdateHandler;

trait TelegramUpdateHandlerProviderTrait
{
    public function getTelegramUpdateHandler(): TelegramUpdateHandler
    {
        return static::getContainer()->get('app.telegram_update_handler');
    }
}