<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Exception\Telegram\TelegramException;
use App\Service\Telegram\Telegram;
use InvalidArgumentException;

class TelegramWebhookUpdater
{
    /**
     * @param Telegram $telegram
     * @return void
     * @throws TelegramException
     */
    public function updateTelegramWebhook(Telegram $telegram): void
    {
        $url = $telegram->getOptions()->getWebhookUrl();
        $cert = $telegram->getOptions()->getWebhookCertificatePath();

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