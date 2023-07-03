<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramTranslator;

trait TelegramTranslatorProviderTrait
{
    public function getTelegramTranslator(): TelegramTranslator
    {
        return static::getContainer()->get('app.telegram_translator');
    }
}