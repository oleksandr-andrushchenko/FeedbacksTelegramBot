<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramOptions;
use Longman\TelegramBot\Telegram as TelegramClient;

class TelegramClientFactory
{
    public function createTelegramClient(TelegramOptions $options): TelegramClient
    {
        return new TelegramClient(
            $options->getApiToken(),
            $options->getUsername(),
        );
    }
}
