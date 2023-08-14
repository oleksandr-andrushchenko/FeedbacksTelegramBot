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
            $countryCode = $telegram->getBot()->getCountryCode();
            $country = $this->countryProvider->getCountry($countryCode);
            $localeCode = $this->countryProvider->getCountryDefaultLocale($country);

            $messengerUserTransfer = new MessengerUserTransfer(
                Messenger::telegram,
                (string) $user->getId(),
                $user->getUsername(),
                trim($user->getFirstName() . ' ' . $user->getLastName()),
                $country->getCode(),
                $localeCode,
                $country->getCurrencyCode()
            );
            $messengerUser = $this->messengerUserUpserter->upsertMessengerUser($messengerUserTransfer);
            $this->userUpserter->upsertUserByMessengerUser($messengerUser);
        }

        return $messengerUser;
    }
}