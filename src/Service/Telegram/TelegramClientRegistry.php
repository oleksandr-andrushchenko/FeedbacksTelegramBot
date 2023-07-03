<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramOptions;
use App\Enum\Telegram\TelegramName;
use Longman\TelegramBot\Telegram as TelegramClient;
use WeakMap;

class TelegramClientRegistry
{
    public function __construct(
        private readonly TelegramClientFactory $telegramClientFactory,
        private ?WeakMap $cache = null,
    )
    {
        $this->cache = $this->cache ?? new WeakMap();
    }

    public function getTelegramClient(TelegramName $telegramName, TelegramOptions $telegramOptions): TelegramClient
    {
        if (isset($this->cache[$telegramName])) {
            return $this->cache[$telegramName];
        }

        return $this->cache[$telegramName] = $this->telegramClientFactory->createTelegramClient($telegramOptions);
    }
}