<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Service\Intl\CountryProvider;

class TelegramNewMessengerUserCountryProvider
{
    public function __construct(
        private readonly TelegramUserProvider $userProvider,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function getCountry(Telegram $telegram): ?string
    {
        $countryCode = null;
        $user = $this->userProvider->getTelegramUserByUpdate($telegram->getUpdate());
        $countries = $this->countryProvider->getCountries($user->getLanguageCode());

        if (count($countries) === 1) {
            $countryCode = $countries[0]->getCode();
        } else {
            foreach ($countries as $country) {
                if ($country->getCode() === $telegram->getBot()->getCountryCode()) {
                    $countryCode = $country->getCode();
                    break;
                }
            }
        }

        return $countryCode;
    }
}