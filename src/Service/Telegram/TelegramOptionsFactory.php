<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramOptions;
use App\Exception\Telegram\TelegramOptionsNotFoundException;

class TelegramOptionsFactory
{
    /**
     * @param array $options
     * @return TelegramOptions
     * @throws TelegramOptionsNotFoundException
     */
    public function createDefaultTelegramOptions(array $options): TelegramOptions
    {
        foreach ($options['bots'] as $username => $token) {
            return $this->createTelegramOptions($username, $options);
        }

        throw new TelegramOptionsNotFoundException();
    }

    /**
     * @param string $username
     * @param array $options
     * @return TelegramOptions
     * @throws TelegramOptionsNotFoundException
     */
    public function createTelegramOptions(string $username, array $options): TelegramOptions
    {
        if (!isset($options['bots'][$username])) {
            throw new TelegramOptionsNotFoundException($username);
        }

        return new TelegramOptions(
            $options['key'],
            $options['bots'][$username],
            $username,
            $options['locales'],
            $options['admin_id'],
            $options['check_updates'],
            $options['check_requests'],
            $options['process_admin_only'],
            $options['accept_payments'],
        );
    }
}
