<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Enum\Messenger\Messenger;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Intl\CountryProvider;
use App\Service\Messenger\MessengerUserUpserter;
use App\Service\User\UserUpserter;

class TelegramMessengerUserUpserter
{
    public function __construct(
        private readonly TelegramUserProvider $userProvider,
        private readonly MessengerUserUpserter $messengerUserUpserter,
        private readonly UserUpserter $userUpserter,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function upsertTelegramMessengerUser(Telegram $telegram): ?MessengerUser
    {
        $user = $this->userProvider->getTelegramUserByUpdate($telegram->getUpdate());

        if ($user === null) {
            $messengerUser = null;
        } else {
            $countryCode = null;
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

            $messengerUserTransfer = new MessengerUserTransfer(
                Messenger::telegram,
                (string) $user->getId(),
                $user->getUsername(),
                trim($user->getFirstName() . ' ' . $user->getLastName()),
                $countryCode,
                $telegram->getBot()->getLocaleCode(),
            );
            $messengerUser = $this->messengerUserUpserter->upsertMessengerUser($messengerUserTransfer);
            $this->userUpserter->upsertUserByMessengerUser($messengerUser);
        }

        return $messengerUser;
    }
}