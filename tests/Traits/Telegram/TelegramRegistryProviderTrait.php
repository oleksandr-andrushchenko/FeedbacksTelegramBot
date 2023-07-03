<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramRegistry;

trait TelegramRegistryProviderTrait
{
    public function getTelegramRegistry(): TelegramRegistry
    {
        return static::getContainer()->get('app.telegram_registry');
    }
}