<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramAwareHelper;

trait TelegramAwareHelperProviderTrait
{
    public function getTelegramAwareHelper(): TelegramAwareHelper
    {
        return static::getContainer()->get('app.telegram_aware_helper');
    }
}