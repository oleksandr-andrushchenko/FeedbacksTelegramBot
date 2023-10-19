<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Chat;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotMatchesProvider;
use App\Service\Telegram\Bot\View\TelegramBotLinkViewProvider;
use Longman\TelegramBot\Entities\Keyboard;

class TelegramBotMatchesChatSender
{
    public function __construct(
        private readonly TelegramBotMatchesProvider $provider,
        private readonly TelegramBotLinkViewProvider $linkViewProvider,
    )
    {
    }

    public function sendTelegramBotMatchesIfNeed(TelegramBotAwareHelper $tg, Keyboard $keyboard = null): void
    {
        $bots = $this->provider->getTelegramBotMatches(
            $tg->getBot()->getMessengerUser()->getUser(),
            $tg->getBot()->getEntity()->getGroup()
        );

        if (count($bots) === 0) {
            return;
        }

        foreach ($bots as $bot) {
            if ($bot->getId() === $tg->getBot()->getEntity()->getId()) {
                return;
            }
        }

        $message = $tg->trans('reply.better_bot_match');
        $message = $tg->infoText($message);
        $message .= ":\n";
        $message .= implode(
            "\n",
            array_map(
                fn (TelegramBot $bot): string => $this->linkViewProvider->getTelegramBotLinkView($bot),
                $bots
            )
        );

        $tg->reply($message, keyboard: $keyboard);
    }
}
