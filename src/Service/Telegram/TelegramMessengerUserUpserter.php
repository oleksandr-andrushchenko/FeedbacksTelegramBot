<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Enum\Messenger\Messenger;
use App\Object\Messenger\MessengerUserTransfer;
use App\Service\Messenger\MessengerUserUpserter;
use App\Service\User\UserUpserter;

class TelegramMessengerUserUpserter
{
    public function __construct(
        private readonly TelegramUserProvider $userProvider,
        private readonly MessengerUserUpserter $messengerUserUpserter,
        private readonly UserUpserter $userUpserter,
    )
    {
    }

    public function upsertTelegramMessengerUser(Telegram $telegram): ?MessengerUser
    {
        $user = $this->userProvider->getTelegramUserByUpdate($telegram->getUpdate());

        if ($user === null) {
            $messengerUser = null;
        } else {
            $messengerUserTransfer = new MessengerUserTransfer(
                Messenger::telegram,
                (string) $user->getId(),
                $user->getUsername(),
                trim($user->getFirstName() . ' ' . $user->getLastName()),
                $user->getLanguageCode()
            );
            $messengerUser = $this->messengerUserUpserter->upsertMessengerUser($messengerUserTransfer);
            $this->userUpserter->upsertUserByMessengerUser($messengerUser);
        }

        return $messengerUser;
    }
}