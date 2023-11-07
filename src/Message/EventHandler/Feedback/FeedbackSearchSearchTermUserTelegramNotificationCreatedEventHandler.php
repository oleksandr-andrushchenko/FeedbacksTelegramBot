<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\NotifyActivityReceiversCommand;
use App\Message\Event\Feedback\FeedbackSearchSearchTermUserTelegramNotificationCreatedEvent;
use App\Repository\Feedback\FeedbackSearchSearchTermUserTelegramNotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSearchSearchTermUserTelegramNotificationCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackSearchSearchTermUserTelegramNotificationRepository $feedbackSearchSearchTermUserTelegramNotificationRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackSearchSearchTermUserTelegramNotificationCreatedEvent $event): void
    {
        $notification = $event->getNotification() ?? $this->feedbackSearchSearchTermUserTelegramNotificationRepository->find($event->getNotificationId());

        if ($notification === null) {
            $this->logger->warning(sprintf('No notification was found in %s for %s id', __CLASS__, $event->getNotificationId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyActivityReceiversCommand(entity: $notification));
    }
}