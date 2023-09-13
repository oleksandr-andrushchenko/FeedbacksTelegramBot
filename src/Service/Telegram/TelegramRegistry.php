<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;

class TelegramRegistry
{
    public function __construct(
        private readonly TelegramFactory $telegramFactory,
        /**
         * @var Telegram[]|null
         */
        private ?array $cache = null,
    )
    {
        $this->cache = [];
    }

    /**
     * @param string $username
     * @return Telegram
     * @throws TelegramNotFoundException
     */
    public function getTelegram(string $username): Telegram
    {
        if (array_key_exists($username, $this->cache)) {
            return $this->cache[$username];
        }

        $this->cache[$username] = $this->telegramFactory->createTelegram($username);

        return $this->cache[$username];
    }
}