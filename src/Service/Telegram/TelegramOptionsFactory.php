<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramOptions;

class TelegramOptionsFactory
{
    public function createTelegramOptions(array $options): TelegramOptions
    {
        return new TelegramOptions(
            $options['api_token'],
            $options['username'],
            $options['webhook_url'],
            $options['webhook_certificate_path'],
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
