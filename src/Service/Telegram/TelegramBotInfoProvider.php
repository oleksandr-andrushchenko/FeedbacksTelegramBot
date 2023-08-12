<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Api\TelegramWebhookInfoProvider;

class TelegramBotInfoProvider
{
    public function __construct(
        private readonly TelegramRegistry $registry,
        private readonly TelegramWebhookInfoProvider $webhookInfoProvider,
    )
    {
    }

    public function getTelegramBotInfo(TelegramBot $bot): array
    {
        $telegram = $this->registry->getTelegram($bot->getUsername());
        $webhookInfo = $this->webhookInfoProvider->getTelegramWebhookInfo($telegram)->getUrl();

        $row = [
            'username' => $bot->getUsername(),
            'token' => '***',
            'webhook' => $webhookInfo === '' ? 'Inactive' : 'Active',
            'country' => $bot->getCountryCode(),
            'locale' => $bot->getLocaleCode(),
            'primary' => $bot->getPrimaryBot() === null ? 'Yes' : sprintf('No (%s)', $bot->getPrimaryBot()->getUsername()),
            'check_updates' => $bot->checkUpdates() ? 'Yes' : 'No',
            'check_requests' => $bot->checkRequests() ? 'Yes' : 'No',
            'accept_payments' => $bot->acceptPayments() ? 'Yes' : 'No',
            'admin_only' => $bot->adminOnly() ? 'Yes' : 'No',
        ];

        foreach ($telegram->getOptions()->getLocaleCodes() as $localeCode) {
            $row['name_' . $localeCode] = $telegram->getMyName(['language_code' => $localeCode])->getResult()->getName();
            $row['short_description_' . $localeCode] = $telegram->getMyShortDescription(['language_code' => $localeCode])->getResult()->getShortDescription();
            $row['description_' . $localeCode] = $telegram->getMyDescription(['language_code' => $localeCode])->getResult()->getDescription();
        }

        return array_merge($row, [
            'group_locales' => join(', ', $telegram->getOptions()->getLocaleCodes()),
            'group_admin_id' => $telegram->getOptions()->getAdminId(),
        ]);
    }
}