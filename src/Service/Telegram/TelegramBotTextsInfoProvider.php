<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;

class TelegramBotTextsInfoProvider
{
    public function __construct(
        private readonly TelegramRegistry $registry,
    )
    {
    }

    public function getTelegramBotTextsInfo(TelegramBot $bot): array
    {
        $telegram = $this->registry->getTelegram($bot);

        $data = [];

        return [
            'name' => $telegram->getMyName($data)->getResult()->getName(),
            'short_description' => $telegram->getMyShortDescription($data)->getResult()->getShortDescription(),
            'description' => $telegram->getMyDescription($data)->getResult()->getDescription(),
        ];
    }
}