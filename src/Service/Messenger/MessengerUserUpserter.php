<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Repository\Messenger\MessengerUserRepository;
use App\Service\IdGenerator;
use App\Transfer\Messenger\MessengerUserTransfer;
use Doctrine\ORM\EntityManagerInterface;

class MessengerUserUpserter
{
    public function __construct(
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IdGenerator $idGenerator,
    )
    {
    }

    public function upsertMessengerUser(MessengerUserTransfer $transfer, bool $withUser = false): MessengerUser
    {
        $messengerUser = $this->messengerUserRepository->findOneByMessengerAndIdentifier(
            $transfer->getMessenger(),
            $transfer->getId(),
            withUser: $withUser,
        );

        if ($messengerUser === null) {
            $messengerUser = new MessengerUser(
                $this->idGenerator->generateId(),
                $transfer->getMessenger(),
                $transfer->getId()
            );
            $this->entityManager->persist($messengerUser);
        }

        if (!empty($transfer->getUsername())) {
            $messengerUser->setUsername($transfer->getUsername());
        }
        if (empty($messengerUser->getName()) && !empty($transfer->getName())) {
            $messengerUser->setName($transfer->getName());
        }
        if (!empty($transfer->getBotId())) {
            $messengerUser->addBotId($transfer->getBotId());
        }

        return $messengerUser;
    }
}
