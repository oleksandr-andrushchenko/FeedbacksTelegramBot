<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\LogActivityCommand;
use App\Message\Event\Feedback\FeedbackSearchUserTelegramNotificationCreatedEvent;
use App\Repository\Feedback\FeedbackSearchUserTelegramNotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSearchUserTelegramNotificationCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackSearchUserTelegramNotificationRepository $feedbackSearchUserTelegramNotificationRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackSearchUserTelegramNotificationCreatedEvent $event): void
    {
        $notification = $event->getNotification() ?? $this->feedbackSearchUserTelegramNotificationRepository->find($event->getNotificationId());

        if ($notification === null) {
            $this->logger->warning(sprintf('No notification was found in %s for %s id', __CLASS__, $event->getNotificationId()));
            return;
        }

        $this->commandBus->dispatch(new LogActivityCommand(entity: $notification));
    }
}