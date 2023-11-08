<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\NotifyActivityAdminsCommand;
use App\Message\Event\Feedback\FeedbackLookupTelegramNotificationCreatedEvent;
use App\Repository\Feedback\FeedbackLookupTelegramNotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackLookupTelegramNotificationCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackLookupTelegramNotificationRepository $feedbackLookupUserTelegramNotificationRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackLookupTelegramNotificationCreatedEvent $event): void
    {
        $notification = $event->getNotification() ?? $this->feedbackLookupUserTelegramNotificationRepository->find($event->getNotificationId());

        if ($notification === null) {
            $this->logger->warning(sprintf('No notification was found in %s for %s id', __CLASS__, $event->getNotificationId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyActivityAdminsCommand(entity: $notification));
    }
}