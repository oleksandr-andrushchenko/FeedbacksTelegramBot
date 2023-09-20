<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;

class TelegramRegistry
{
    public function __construct(
        private readonly TelegramFactory $factory,
        /**
         * @var Telegram[]|null
         */
        private ?array $cache = null,
    )
    {
        $this->cache = [];
    }

    public function getTelegram(TelegramBot $bot): Telegram
    {
        $username = $bot->getUsername();

        if (array_key_exists($username, $this->cache)) {
            return $this->cache[$username];
        }

        $this->cache[$username] = $this->factory->createTelegram($bot);

        return $this->cache[$username];
    }
}