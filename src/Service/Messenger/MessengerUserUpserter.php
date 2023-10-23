<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Repository\Messenger\MessengerUserRepository;
use App\Service\IdGenerator;
use App\Transfer\Messenger\MessengerUserTransfer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class MessengerUserUpserter
{
    public function __construct(
        private readonly MessengerUserRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IdGenerator $idGenerator,
    )
    {
    }

    public function upsertMessengerUser(MessengerUserTransfer $transfer): MessengerUser
    {
        $messengerUser = $this->repository->findOneByMessengerAndIdentifier(
            $transfer->getMessenger(), $transfer->getId()
        );

        if ($messengerUser === null) {
            $messengerUser = new MessengerUser(
                $this->idGenerator->generateId(),
                $transfer->getMessenger(),
                $transfer->getId()
            );
            $this->entityManager->persist($messengerUser);
        } else {
            $messengerUser->setUpdatedAt(new DateTimeImmutable());
        }

        if (empty($messengerUser->getUsername()) && !empty($transfer->getUsername())) {
            $messengerUser->setUsername($transfer->getUsername());
        }
        if (empty($messengerUser->getName()) && !empty($transfer->getName())) {
            $messengerUser->setName($transfer->getName());
        }

        return $messengerUser;
    }
}
