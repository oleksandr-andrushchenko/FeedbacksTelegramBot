<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Repository\Telegram\Bot\TelegramBotUpdateRepository;

trait TelegramBotUpdateRepositoryProviderTrait
{
    public function getTelegramBotUpdateRepository(): TelegramBotUpdateRepository
    {
        return static::getContainer()->get('app.repository.telegram_bot_update');
    }
}