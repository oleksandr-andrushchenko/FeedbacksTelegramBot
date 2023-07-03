<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Enum\Telegram\TelegramName;
use App\Exception\Telegram\TelegramException;
use WeakMap;

class TelegramRegistry
{
    public function __construct(
        private readonly TelegramFactory $telegramFactory,
        private ?WeakMap $cache = null,
    )
    {
        $this->cache = $this->cache ?? new WeakMap();
    }

    /**
     * @param string|TelegramName $telegramName
     * @return Telegram
     * @throws TelegramException
     */
    public function getTelegram(string|TelegramName $telegramName): Telegram
    {
        $telegramName = is_string($telegramName) ? TelegramName::fromName($telegramName) : $telegramName;

        if (isset($this->cache[$telegramName])) {
            return $this->cache[$telegramName];
        }

        return $this->cache[$telegramName] = $this->telegramFactory->createTelegram($telegramName);
    }
}