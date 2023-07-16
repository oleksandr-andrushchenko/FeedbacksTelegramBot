<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Exception\Telegram\TelegramException;
use App\Service\Telegram\Telegram;

class TelegramWebhookRemover
{
    /**
     * @param Telegram $telegram
     * @return void
     * @throws TelegramException
     */
    public function removeTelegramWebhook(Telegram $telegram): void
    {
        $telegram->deleteWebhook();
    }
}