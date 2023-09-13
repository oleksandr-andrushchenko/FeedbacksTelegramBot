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
        $telegram = $this->registry->getTelegram($bot->getUsername());

        $row = [];
        $localeCode = $bot->getLocaleCode();
        $params = ['language_code' => $localeCode];

        $row['name'] = $telegram->getMyName($params)->getResult()->getName();
        $row['short_description'] = $telegram->getMyShortDescription($params)->getResult()->getShortDescription();
        $row['description'] = $telegram->getMyDescription($params)->getResult()->getDescription();

        return $row;
    }
}