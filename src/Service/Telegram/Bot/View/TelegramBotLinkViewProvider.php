<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\View;

use App\Entity\Telegram\TelegramBot;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\TelegramLinkProvider;

class TelegramBotLinkViewProvider
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly TelegramLinkProvider $linkProvider,
    )
    {
    }

    public function getTelegramBotLinkView(TelegramBot $bot): string
    {
        $country = $this->countryProvider->getCountry($bot->getCountryCode());
        $countryIcon = $this->countryProvider->getCountryIcon($country);
        $locale = $this->localeProvider->getLocale($bot->getLocaleCode());
        $localeIcon = $this->localeProvider->getLocaleIcon($locale);
        $link = $this->linkProvider->getTelegramLink($bot->getUsername());

        $localeIcon = $countryIcon === $localeIcon ? '' : ('/' . $localeIcon);

        return sprintf('%s%s <b><a href="%s">%s</a></b>', $countryIcon, $localeIcon, $link, $bot->getName());
    }
}