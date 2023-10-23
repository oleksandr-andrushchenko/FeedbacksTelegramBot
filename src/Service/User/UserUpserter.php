<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Service\IdGenerator;
use App\Transfer\Messenger\MessengerUserTransfer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class UserUpserter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IdGenerator $idGenerator,
    )
    {
    }

    public function upsertUserByMessengerUser(MessengerUser $messengerUser, MessengerUserTransfer $transfer): User
    {
        $user = $messengerUser->getUser();

        if ($user === null) {
            $user = new User(
                $this->idGenerator->generateId()
            );
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
        if (empty($user->getCountryCode()) && !empty($transfer->getCountryCode())) {
            $user->setCountryCode($transfer->getCountryCode());
        }
        if ($user->getLocaleCode() === null && $transfer->getLocaleCode() !== null) {
            $user->setLocaleCode($transfer->getLocaleCode());
        }
        if (empty($user->getCurrencyCode()) && !empty($transfer->getCurrencyCode())) {
            $user->setCurrencyCode($transfer->getCurrencyCode());
        }
        if (empty($user->getTimezone()) && !empty($transfer->getTimezone())) {
            $user->setTimezone($transfer->getTimezone());
        }

        return $user;
    }
}