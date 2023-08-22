<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Repository\Messenger\MessengerUserRepository;
use App\Object\Messenger\MessengerUserTransfer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class MessengerUserUpserter
{
    public function __construct(
        private readonly MessengerUserRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function upsertMessengerUser(MessengerUserTransfer $messengerUserTransfer): MessengerUser
    {
        $messengerUser = $this->repository->findOneByMessengerAndIdentifier(
            $messengerUserTransfer->getMessenger(), $messengerUserTransfer->getId()
        );

        if ($messengerUser === null) {
            $messengerUser = new MessengerUser(
                $messengerUserTransfer->getMessenger(),
                $messengerUserTransfer->getId(),
                username: $messengerUserTransfer->getUsername(),
                name: $messengerUserTransfer->getName(),
                localeCode: $messengerUserTransfer->getLocaleCode(),
            );
            $this->entityManager->persist($messengerUser);

            return $messengerUser;
        }

        if ($messengerUserTransfer->getUsername() !== null) {
            $messengerUser->setUsername($messengerUserTransfer->getUsername());
        }

        if ($messengerUserTransfer->getName() !== null) {
            $messengerUser->setName($messengerUserTransfer->getName());
        }

        $messengerUser->setUpdatedAt(new DateTimeImmutable());

        return $messengerUser;
    }
}
