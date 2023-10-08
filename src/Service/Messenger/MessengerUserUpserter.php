<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Repository\Messenger\MessengerUserRepository;
use App\Transfer\Messenger\MessengerUserTransfer;
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
                $messengerUserTransfer->getId()
            );
            $this->entityManager->persist($messengerUser);
        } else {
            $messengerUser->setUpdatedAt(new DateTimeImmutable());
        }

        if (empty($messengerUser->getUsername()) && !empty($messengerUserTransfer->getUsername())) {
            $messengerUser->setUsername($messengerUserTransfer->getUsername());
        }
        if (empty($messengerUser->getName()) && !empty($messengerUserTransfer->getName())) {
            $messengerUser->setName($messengerUserTransfer->getName());
        }

        return $messengerUser;
    }
}
