<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\BetterMatchTelegramBotProvider;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramLinkProvider;
use Longman\TelegramBot\Entities\Keyboard;

class BetterMatchBotTelegramChatSender
{
    public function __construct(
        private readonly BetterMatchTelegramBotProvider $provider,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly TelegramLinkProvider $linkProvider,
    )
    {
    }

    public function sendBetterMatchBotIfNeed(TelegramAwareHelper $tg, Keyboard $keyboard = null): null
    {
        $telegram = $tg->getTelegram();

        $bot = $this->provider->getBetterMatchTelegramBot($telegram->getMessengerUser(), $telegram->getBot());

        if ($bot === null) {
            return null;
        }

        $user = $telegram->getMessengerUser()->getUser();

        $country = $this->countryProvider->getCountry($user->getCountryCode());
        $locale = $this->localeProvider->getLocale($user->getLocaleCode());
        $link = $this->linkProvider->getTelegramLink($bot->getUsername());

        $parameters = [
            'country' => sprintf('<u>%s</u>', $this->countryProvider->getCountryComposeName($country)),
            'locale' => sprintf('<u>%s</u>', $this->localeProvider->getLocaleComposeName($locale)),
        ];
        $message = $tg->trans('reply.better_bot_match', $parameters);
        $message = $tg->infoText($message);
        $message .= "\n";
        $message .= sprintf('<b><a href="%s">%s</a></b>', $link, $bot->getName());

        $tg->reply($message, keyboard: $keyboard);

        return null;
    }
}
