<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\NotifyActivityAdminsCommand;
use App\Message\Event\Feedback\FeedbackSearchTelegramNotificationCreatedEvent;
use App\Repository\Feedback\FeedbackSearchTelegramNotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSearchTelegramNotificationCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackSearchTelegramNotificationRepository $feedbackSearchUserTelegramNotificationRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackSearchTelegramNotificationCreatedEvent $event): void
    {
        $notification = $event->getNotification() ?? $this->feedbackSearchUserTelegramNotificationRepository->find($event->getNotificationId());

        if ($notification === null) {
            $this->logger->warning(sprintf('No notification was found in %s for %s id', __CLASS__, $event->getNotificationId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyActivityAdminsCommand(entity: $notification));
    }
}