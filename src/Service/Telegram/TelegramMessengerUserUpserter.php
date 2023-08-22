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
            return null;
        }

        $countryCode = $telegram->getBot()->getCountryCode();
        $country = $this->countryProvider->getCountry($countryCode);

        $messengerUserTransfer = new MessengerUserTransfer(
            Messenger::telegram,
            (string) $user->getId(),
            username: $user->getUsername(),
            name: trim($user->getFirstName() . ' ' . $user->getLastName()),
            countryCode: $country->getCode(),
            localeCode: $country->getLocaleCodes()[0] ?? null,
            currencyCode: $country->getCurrencyCode(),
            timezone: $country->getTimezones()[0] ?? null
        );
        $messengerUser = $this->messengerUserUpserter->upsertMessengerUser($messengerUserTransfer);
        $this->userUpserter->upsertUserByMessengerUser($messengerUser, $messengerUserTransfer);

        return $messengerUser;
    }
}