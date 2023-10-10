<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotRegistry;
use App\Service\Telegram\Bot\TelegramBotWebhookUrlGenerator;

class TelegramBotWebhookSyncer
{
    public function __construct(
        private readonly TelegramBotRegistry $registry,
        private readonly TelegramBotWebhookUrlGenerator $webhookUrlGenerator,
    )
    {
    }

    public function syncTelegramWebhook(TelegramBot $botEntity): void
    {
        $bot = $this->registry->getTelegramBot($botEntity);
        $url = $this->webhookUrlGenerator->generate($bot->getEntity()->getUsername());

        $bot->setWebhook($url);

        $bot->getEntity()->setWebhookSynced(true);
    }
}