<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\Telegram\TelegramException;

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