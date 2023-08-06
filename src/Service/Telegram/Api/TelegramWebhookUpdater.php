<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramWebhookUrlGenerator;
use InvalidArgumentException;

class TelegramWebhookUpdater
{
    public function __construct(
        private readonly TelegramWebhookUrlGenerator $webhookUrlGenerator,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @return void
     */
    public function updateTelegramWebhook(Telegram $telegram): void
    {
        $url = $this->webhookUrlGenerator->generate($telegram->getOptions()->getUsername());
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