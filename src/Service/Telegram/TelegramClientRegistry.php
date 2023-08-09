<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
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

    public function getTelegramClient(TelegramBot $bot): TelegramClient
    {
        $key = $bot->getUsername();

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = $this->clientFactory->createTelegramClient($bot);
    }
}