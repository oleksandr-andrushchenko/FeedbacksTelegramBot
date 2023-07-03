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
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function upsertMessengerUser(MessengerUserTransfer $messengerUserTransfer): MessengerUser
    {
        $messengerUser = $this->messengerUserRepository->findOneByMessengerAndIdentifier(
            $messengerUserTransfer->getMessenger(), $messengerUserTransfer->getId()
        );

        if ($messengerUser === null) {
            $messengerUser = new MessengerUser(
                $messengerUserTransfer->getMessenger(),
                $messengerUserTransfer->getId(),
                $messengerUserTransfer->getUsername(),
                $messengerUserTransfer->getName(),
                $messengerUserTransfer->getLanguageCode()
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

        if ($messengerUserTransfer->getLanguageCode() !== null) {
            $messengerUser->setLanguageCode($messengerUserTransfer->getLanguageCode());
        }

        $messengerUser->setUpdatedAt(new DateTimeImmutable());

        return $messengerUser;
    }
}
