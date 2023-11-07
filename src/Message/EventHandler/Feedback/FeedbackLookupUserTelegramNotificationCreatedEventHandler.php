<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\NotifyActivityReceiversCommand;
use App\Message\Event\Feedback\FeedbackLookupUserTelegramNotificationCreatedEvent;
use App\Repository\Feedback\FeedbackLookupUserTelegramNotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackLookupUserTelegramNotificationCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackLookupUserTelegramNotificationRepository $feedbackLookupUserTelegramNotificationRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackLookupUserTelegramNotificationCreatedEvent $event): void
    {
        $notification = $event->getNotification() ?? $this->feedbackLookupUserTelegramNotificationRepository->find($event->getNotificationId());

        if ($notification === null) {
            $this->logger->warning(sprintf('No notification was found in %s for %s id', __CLASS__, $event->getNotificationId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyActivityReceiversCommand(entity: $notification));
    }
}