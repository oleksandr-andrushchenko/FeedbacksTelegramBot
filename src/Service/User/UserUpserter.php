<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Object\Messenger\MessengerUserTransfer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class UserUpserter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function upsertUserByMessengerUser(MessengerUser $messengerUser, MessengerUserTransfer $messengerUserTransfer): User
    {
        $user = $messengerUser->getUser();

        if ($user === null) {
            $user = new User();
            $this->entityManager->persist($user);

            $messengerUser->setUser($user);
        } else {
            $user->setUpdatedAt(new DateTimeImmutable());
        }

        if (empty($user->getUsername()) && !empty($messengerUser->getUsername())) {
            $user->setUsername($messengerUser->getUsername());
        }
        if (empty($user->getName()) && !empty($messengerUser->getName())) {
            $user->setName($messengerUser->getName());
        }
        if (empty($user->getCountryCode()) && !empty($messengerUserTransfer->getCountryCode())) {
            $user->setCountryCode($messengerUserTransfer->getCountryCode());
        }
        if ($user->getLocaleCode() === null && $messengerUser->getLocaleCode() !== null) {
            $user->setLocaleCode($messengerUser->getLocaleCode());
        }
        if (empty($user->getCurrencyCode()) && !empty($messengerUserTransfer->getCurrencyCode())) {
            $user->setCurrencyCode($messengerUserTransfer->getCurrencyCode());
        }
        if (empty($user->getTimezone()) && !empty($messengerUserTransfer->getTimezone())) {
            $user->setTimezone($messengerUserTransfer->getTimezone());
        }

        return $user;
    }
}