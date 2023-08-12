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
        foreach ($telegram->getOptions()->getLocaleCodes() as $localeCode) {
            $params = ['language_code' => $localeCode];
            $row['name_' . $localeCode] = $telegram->getMyName($params)->getResult()->getName();
            $row['short_description_' . $localeCode] = $telegram->getMyShortDescription($params)->getResult()->getShortDescription();
            $row['description_' . $localeCode] = $telegram->getMyDescription($params)->getResult()->getDescription();
        }

        return $row;
    }
}