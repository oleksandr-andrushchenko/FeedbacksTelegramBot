<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\Telegram\TelegramException;
use Longman\TelegramBot\Entities\WebhookInfo;

class TelegramWebhookInfoProvider
{
    /**
     * @param Telegram $telegram
     * @return WebhookInfo
     * @throws TelegramException
     */
    public function getTelegramWebhookInfo(Telegram $telegram): WebhookInfo
    {
        return $telegram->getWebhookInfo()->getResult();
    }
}