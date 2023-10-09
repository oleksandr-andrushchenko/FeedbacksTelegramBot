<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Chat;

use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotMatchesProvider;
use App\Service\Telegram\TelegramLinkProvider;
use Longman\TelegramBot\Entities\Keyboard;

class TelegramBotMatchesChatSender
{
    public function __construct(
        private readonly TelegramBotMatchesProvider $provider,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly TelegramLinkProvider $linkProvider,
    )
    {
    }

    public function sendTelegramBotMatchesIfNeed(TelegramBotAwareHelper $tg, Keyboard $keyboard = null): null
    {
        $bots = $this->provider->getTelegramBotMatches(
            $tg->getBot()->getMessengerUser()->getUser(),
            $tg->getBot()->getEntity()->getGroup()
        );

        if (count($bots) === 0) {
            return null;
        }

        foreach ($bots as $bot) {
            if ($bot->getId() === $tg->getBot()->getEntity()->getId()) {
                return null;
            }
        }

        $message = $tg->trans('reply.better_bot_match');
        $message = $tg->infoText($message);
        $message .= ":\n\n";

        $botNames = [];

        foreach ($bots as $bot) {
            $country = $this->countryProvider->getCountry($bot->getCountryCode());
            $countryIcon = $this->countryProvider->getCountryIcon($country);
            $locale = $this->localeProvider->getLocale($bot->getLocaleCode());
            $localeIcon = $this->localeProvider->getLocaleIcon($locale);
            $link = $this->linkProvider->getTelegramLink($bot->getUsername());

            $localeIcon = $countryIcon === $localeIcon ? '' : ('/' . $localeIcon);
            $botNames[] = sprintf('%s%s <b><a href="%s">%s</a></b>', $countryIcon, $localeIcon, $link, $bot->getName());
        }

        $message .= join("\n", $botNames);

        $tg->reply($message, keyboard: $keyboard);

        return null;
    }
}
