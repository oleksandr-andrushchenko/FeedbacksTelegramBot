<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramBotDescriptionsSyncer
{
    public function __construct(
        private readonly TelegramBotRegistry $telegramBotRegistry,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function syncTelegramDescriptions(TelegramBot $botEntity): void
    {
        $bot = $this->telegramBotRegistry->getTelegramBot($botEntity);

        $bot->setMyName([
            'name' => $this->getMyName($botEntity),
        ]);

        $bot->setMyDescription([
            'description' => $this->getMyDescription($botEntity),
        ]);

        $bot->setMyShortDescription([
            'short_description' => $this->getMyShortDescription($botEntity),
        ]);

        $bot->getEntity()->setDescriptionsSynced(true);
    }

    private function getMyName(TelegramBot $bot): string
    {
        return $bot->getName();
    }

    private function getMyDescription(TelegramBot $botEntity): string
    {
        $myDescription = "\n";
        $myDescription .= 'ℹ️ ';
        $myDescription .= $this->getMyShortDescription($botEntity);

        return $myDescription;
    }

    private function getMyShortDescription(TelegramBot $botEntity): string
    {
        $group = $botEntity->getGroup();
        $localeCode = $botEntity->getLocaleCode();

        return $this->translator->trans(
            'short',
            domain: sprintf('%s.tg.descriptions', $group->name),
            locale: $localeCode
        );
    }
}