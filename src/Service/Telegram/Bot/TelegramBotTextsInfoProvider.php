<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;

class TelegramBotTextsInfoProvider
{
    public function __construct(
        private readonly TelegramBotRegistry $registry,
    )
    {
    }

    public function getTelegramBotTextsInfo(TelegramBot $botEntity): array
    {
        $bot = $this->registry->getTelegramBot($botEntity);

        $data = [];

        return [
            'name' => $bot->getMyName($data)->getResult()->getName(),
            'short_description' => $bot->getMyShortDescription($data)->getResult()->getShortDescription(),
            'description' => $bot->getMyDescription($data)->getResult()->getDescription(),
        ];
    }
}