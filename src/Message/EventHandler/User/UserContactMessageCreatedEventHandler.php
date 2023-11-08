<?php

declare(strict_types=1);

namespace App\Message\EventHandler\User;

use App\Message\Command\NotifyActivityAdminsCommand;
use App\Message\Event\User\UserContactMessageCreatedEvent;
use App\Repository\User\UserContactMessageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class UserContactMessageCreatedEventHandler
{
    public function __construct(
        private readonly UserContactMessageRepository $userContactMessageRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(UserContactMessageCreatedEvent $event): void
    {
        $message = $event->getMessage() ?? $this->userContactMessageRepository->find($event->getMessageId());

        if ($message === null) {
            $this->logger->warning(sprintf('No user contact message was found in %s for %s id', __CLASS__, $event->getMessageId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyActivityAdminsCommand(entity: $message));
    }
}