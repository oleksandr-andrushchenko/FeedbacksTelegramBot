<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class UserUpserter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function upsertUserByMessengerUser(MessengerUser $messengerUser): User
    {
        $user = $messengerUser->getUser();

        if ($user === null) {
            $user = new User(
                $messengerUser->getUsername(),
                $messengerUser->getName(),
                $messengerUser->getCountryCode(),
                $messengerUser->getLocaleCode()
            );

            $this->entityManager->persist($user);

            $messengerUser->setUser($user);

            return $user;
        }

        if (empty($user->getUsername()) && !empty($messengerUser->getUsername())) {
            $user->setUsername($messengerUser->getUsername());
        }

        if (empty($user->getName()) && !empty($messengerUser->getName())) {
            $user->setName($messengerUser->getName());
        }

        if ($user->getCountryCode() === null && $messengerUser->getCountryCode() !== null) {
            $user->setCountryCode($messengerUser->getCountryCode());
        }

        if ($user->getLocaleCode() === null && $messengerUser->getLocaleCode() !== null) {
            $user->setLocaleCode($messengerUser->getLocaleCode());
        }

        $user->setUpdatedAt(new DateTimeImmutable());

        return $user;
    }
}