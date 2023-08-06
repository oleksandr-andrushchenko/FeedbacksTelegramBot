<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramGroupOptions;

class TelegramGroupOptionsFactory
{
    public function createTelegramGroupOptions(array $options): TelegramGroupOptions
    {
        return new TelegramGroupOptions(
            $options['key'],
            $options['bots'],
            $options['locales'],
            array_map(fn ($id) => (int) $id, $options['admin_ids']),
            array_map(fn ($id) => (int) $id, $options['admin_chat_ids']),
            $options['check_updates'],
            $options['check_requests'],
            $options['process_admin_only'],
            $options['accept_payments'],
        );
    }
}
