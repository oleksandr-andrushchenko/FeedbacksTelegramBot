<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotRegistry;

class TelegramBotWebhookRemover
{
    public function __construct(
        private readonly TelegramBotRegistry $registry,
    )
    {
    }

    public function removeTelegramWebhook(TelegramBot $botEntity): void
    {
        $bot = $this->registry->getTelegramBot($botEntity);

        $bot->deleteWebhook();

        $bot->getEntity()->setWebhookSynced(false);
    }
}