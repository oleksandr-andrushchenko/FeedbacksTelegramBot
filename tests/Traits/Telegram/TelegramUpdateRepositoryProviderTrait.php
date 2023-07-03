<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Repository\Telegram\TelegramUpdateRepository;

trait TelegramUpdateRepositoryProviderTrait
{
    public function getTelegramUpdateRepository(): TelegramUpdateRepository
    {
        return static::getContainer()->get('app.repository.telegram_update');
    }
}