<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Enum\Telegram\TelegramGroup;
use WeakMap;

class TelegramChannelRegistry
{
    public function __construct(
        private readonly TelegramChannelFactory $channelFactory,
        private ?WeakMap $cache = null,
    )
    {
        $this->cache = $this->cache ?? new WeakMap();
    }

    public function getTelegramChannel(TelegramGroup $telegramName): TelegramChannelInterface
    {
        if (isset($this->cache[$telegramName])) {
            return $this->cache[$telegramName];
        }

        return $this->cache[$telegramName] = $this->channelFactory->createTelegramChannel($telegramName);
    }
}