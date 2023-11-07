<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\NotifyActivityReceiversCommand;
use App\Message\Event\Feedback\FeedbackSearchTermUserTelegramNotificationCreatedEvent;
use App\Repository\Feedback\FeedbackSearchTermUserTelegramNotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSearchTermUserTelegramNotificationCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackSearchTermUserTelegramNotificationRepository $feedbackSearchTermUserTelegramNotificationRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackSearchTermUserTelegramNotificationCreatedEvent $event): void
    {
        $notification = $event->getNotification() ?? $this->feedbackSearchTermUserTelegramNotificationRepository->find($event->getNotificationId());

        if ($notification === null) {
            $this->logger->warning(sprintf('No notification was found in %s for %s id', __CLASS__, $event->getNotificationId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyActivityReceiversCommand(entity: $notification));
    }
}