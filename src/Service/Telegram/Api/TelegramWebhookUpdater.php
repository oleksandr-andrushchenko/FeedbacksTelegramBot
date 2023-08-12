<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\TelegramRegistry;
use App\Service\Telegram\TelegramWebhookUrlGenerator;
use InvalidArgumentException;

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
        $telegram = $this->registry->getTelegram($bot->getUsername());
        $url = $this->webhookUrlGenerator->generate($telegram->getBot()->getUsername());
        $cert = true ? '' : 'any';

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(
                sprintf('Invalid webhook url provided: "%s"', $url)
            );
        }

        if (!empty($cert) && !is_file($cert)) {
            throw new InvalidArgumentException(
                sprintf('Invalid webhook certificate path provided: "%s"', $cert)
            );
        }

        $telegram->setWebhook($url, empty($cert) ? [] : ['certificate' => $cert]);
    }
}