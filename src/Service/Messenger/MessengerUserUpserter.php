<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Message\Event\ActivityEvent;
use App\Repository\Messenger\MessengerUserRepository;
use App\Service\IdGenerator;
use App\Transfer\Messenger\MessengerUserTransfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerUserUpserter
{
    public function __construct(
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
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

        $created = false;

        if ($messengerUser === null) {
            $created = true;
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

        if ($created) {
            $this->eventBus->dispatch(new ActivityEvent(entity: $messengerUser, action: 'created'));
        }

        return $messengerUser;
    }
}
