<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Exception\Telegram\TelegramException;
use Longman\TelegramBot\Entities\WebhookInfo;

class TelegramWebhookInfoProvider
{
    public function __construct(
        private readonly TelegramRegistry $registry,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @return array
     * @throws TelegramException
     */
    public function getTelegramWebhookInfo(TelegramBot $bot): array
    {
        $telegram = $this->registry->getTelegram($bot->getUsername());
        /** @var WebhookInfo $info */
        $info = $telegram->getWebhookInfo()->getResult();

        return [
            'url' => $info->getUrl(),
            'custom_certificate' => $info->getHasCustomCertificate(),
            'pending_update_count' => $info->getPendingUpdateCount(),
            'ip_address' => $info->getIpAddress(),
            'max_connections' => $info->getMaxConnections(),
            'allowed_updates' => $info->getAllowedUpdates(),
            'last_error_date' => $info->getLastErrorDate(),
            'last_error_message' => $info->getLastErrorMessage(),
            'last_synchronization_error_date' => $info->getLastSynchronizationErrorDate(),
        ];
    }
}