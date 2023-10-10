<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotMyCommands;
use App\Service\Telegram\Bot\TelegramBotMyCommandsProvider;
use App\Service\Telegram\Bot\TelegramBotRegistry;

class TelegramBotCommandsRemover
{
    public function __construct(
        private readonly TelegramBotRegistry $registry,
        private readonly TelegramBotMyCommandsProvider $provider,
        private ?array $myCommands = null,
    )
    {
        $this->myCommands = null;
    }

    /**
     * @param TelegramBot $botEntity
     * @return void
     */
    public function removeTelegramCommands(TelegramBot $botEntity): void
    {
        $bot = $this->registry->getTelegramBot($botEntity);
        $this->myCommands = [];

        foreach ($this->provider->getTelegramMyCommands($bot) as $myCommands) {
            $data = [
                'scope' => $myCommands->getScope()->jsonSerialize(),
            ];

            $this->myCommands[] = $myCommands;

            $bot->deleteMyCommands($data);
        }

        $bot->getEntity()->setCommandsSynced(false);
    }

    /**
     * @return array|null|TelegramBotMyCommands[]
     */
    public function getMyCommands(): ?array
    {
        return $this->myCommands;
    }
}