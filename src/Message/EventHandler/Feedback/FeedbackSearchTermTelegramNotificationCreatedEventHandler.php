<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\NotifyActivityAdminsCommand;
use App\Message\Event\Feedback\FeedbackSearchTermTelegramNotificationCreatedEvent;
use App\Repository\Feedback\FeedbackSearchTermTelegramNotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSearchTermTelegramNotificationCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackSearchTermTelegramNotificationRepository $feedbackSearchTermUserTelegramNotificationRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackSearchTermTelegramNotificationCreatedEvent $event): void
    {
        $notification = $event->getNotification() ?? $this->feedbackSearchTermUserTelegramNotificationRepository->find($event->getNotificationId());

        if ($notification === null) {
            $this->logger->warning(sprintf('No notification was found in %s for %s id', __CLASS__, $event->getNotificationId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyActivityAdminsCommand(entity: $notification));
    }
}