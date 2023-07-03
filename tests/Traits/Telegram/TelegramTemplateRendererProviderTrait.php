<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Service\Telegram\TelegramTemplateRenderer;

trait TelegramTemplateRendererProviderTrait
{
    public function getTelegramTemplateRenderer(): TelegramTemplateRenderer
    {
        return static::getContainer()->get('app.telegram_template_renderer');
    }
}