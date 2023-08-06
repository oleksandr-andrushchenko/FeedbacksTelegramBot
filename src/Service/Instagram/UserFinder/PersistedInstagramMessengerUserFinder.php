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
        $user = $this->messengerUserRepository->findOneByMessengerAndUsername(Messenger::instagram, $username);

        if ($user === null) {
            return null;
        }

        return new MessengerUserTransfer(
            $user->getMessenger(),
            $user->getIdentifier(),
            $user->getUsername(),
            $user->getName(),
            $user->getLocaleCode()
        );
    }
}
