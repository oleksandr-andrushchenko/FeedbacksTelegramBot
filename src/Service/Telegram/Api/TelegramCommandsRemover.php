<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramMyCommands;
use App\Service\Telegram\TelegramMyCommandsProvider;

class TelegramCommandsRemover
{
    public function __construct(
        private readonly TelegramMyCommandsProvider $telegramMyCommandsProvider,
        private ?array $myCommands = null,
    )
    {
        $this->myCommands = null;
    }

    /**
     * @param Telegram $telegram
     * @return void
     */
    public function removeTelegramCommands(Telegram $telegram): void
    {
        $this->myCommands = [];

        foreach ($this->telegramMyCommandsProvider->getTelegramMyCommands($telegram) as $myCommands) {
            if (!in_array($myCommands->getLanguageCode(), $telegram->getOptions()->getLanguageCodes(), true)) {
                continue;
            }

            $data = [];

            $data['language_code'] = $myCommands->getLanguageCode();
            $data['scope'] = $myCommands->getScope()->jsonSerialize();

            $this->myCommands[] = $myCommands;

            $telegram->deleteMyCommands($data);
        }
    }

    /**
     * @return array|null|TelegramMyCommands[]
     */
    public function getMyCommands(): ?array
    {
        return $this->myCommands;
    }
}