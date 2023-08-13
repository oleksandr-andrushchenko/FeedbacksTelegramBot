<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;

class TelegramBotInfoProvider
{
    public function getTelegramBotInfo(TelegramBot $bot): array
    {
        return [
            'group' => $bot->getGroup()->name,
            'username' => $bot->getUsername(),
            'texts' => $bot->textsSet() ? 'Yes' : 'No',
            'webhook' => $bot->webhookSet() ? 'Yes' : 'No',
            'commands' => $bot->commandsSet() ? 'Yes' : 'No',
            'country' => $bot->getCountryCode(),
            'locale' => $bot->getLocaleCode(),
            'primary' => $bot->getPrimaryBot() === null ? 'Yes' : sprintf('No (%s)', $bot->getPrimaryBot()->getUsername()),
            'check_updates' => $bot->checkUpdates() ? 'Yes' : 'No',
            'check_requests' => $bot->checkRequests() ? 'Yes' : 'No',
            'accept_payments' => $bot->acceptPayments() ? 'Yes' : 'No',
            'admin_only' => $bot->adminOnly() ? 'Yes' : 'No',
        ];
    }
}