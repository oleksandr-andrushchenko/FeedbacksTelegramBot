<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Repository\Telegram\TelegramBotRepository;

trait TelegramBotRepositoryProviderTrait
{
    public function getTelegramBotRepository(): TelegramBotRepository
    {
        return static::getContainer()->get('app.repository.telegram_bot');
    }
}