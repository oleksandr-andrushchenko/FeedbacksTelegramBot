<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\TelegramRegistry;
use App\Service\Telegram\TelegramWebhookUrlGenerator;

class TelegramWebhookUpdater
{
    public function __construct(
        private readonly TelegramRegistry $registry,
        private readonly TelegramWebhookUrlGenerator $webhookUrlGenerator,
    )
    {
    }

    public function updateTelegramWebhook(TelegramBot $bot): void
    {
        $telegram = $this->registry->getTelegram($bot);
        $url = $this->webhookUrlGenerator->generate($telegram->getBot()->getUsername());

        $telegram->setWebhook($url);

        $bot->setWebhookSet(true);
    }
}