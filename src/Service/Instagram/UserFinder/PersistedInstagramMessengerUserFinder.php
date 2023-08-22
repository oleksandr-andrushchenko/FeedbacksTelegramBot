<?php

declare(strict_types=1);

namespace App\Service\Instagram\UserFinder;

use App\Enum\Messenger\Messenger;
use App\Object\Messenger\MessengerUserTransfer;
use App\Repository\Messenger\MessengerUserRepository;

class PersistedInstagramMessengerUserFinder implements InstagramMessengerUserFinderInterface
{
    public function __construct(
        private readonly MessengerUserRepository $messengerUserRepository,
    )
    {
    }

    public function findInstagramMessengerUser(string $username, $_ = null): ?MessengerUserTransfer
    {
        $messengerUser = $this->messengerUserRepository->findOneByMessengerAndUsername(Messenger::instagram, $username);

        if ($messengerUser === null) {
            return null;
        }

        return new MessengerUserTransfer(
            $messengerUser->getMessenger(),
            $messengerUser->getIdentifier(),
            $messengerUser->getUsername(),
            $messengerUser->getName(),
            $messengerUser->getUser()->getCountryCode(),
            $messengerUser->getLocaleCode(),
            $messengerUser->getUser()->getCurrencyCode()
        );
    }
}
