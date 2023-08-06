<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramOptions;
use Longman\TelegramBot\Telegram as TelegramClient;

class TelegramClientRegistry
{
    public function __construct(
        private readonly TelegramClientFactory $clientFactory,
        private ?array $cache = null,
    )
    {
        $this->cache = [];
    }

    public function getTelegramClient(TelegramOptions $options): TelegramClient
    {
        $key = $options->getUsername();

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = $this->clientFactory->createTelegramClient($options);
    }
}