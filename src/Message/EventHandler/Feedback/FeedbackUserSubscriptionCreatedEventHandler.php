<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\NotifyActivityReceiversCommand;
use App\Message\Event\Feedback\FeedbackUserSubscriptionCreatedEvent;
use App\Repository\Feedback\FeedbackUserSubscriptionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackUserSubscriptionCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackUserSubscriptionRepository $feedbackUserSubscriptionRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackUserSubscriptionCreatedEvent $event): void
    {
        $subscription = $event->getSubscription() ?? $this->feedbackUserSubscriptionRepository->find($event->getSubscriptionId());

        if ($subscription === null) {
            $this->logger->warning(sprintf('No feedback user subscription was found in %s for %s id', __CLASS__, $event->getSubscriptionId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyActivityReceiversCommand(entity: $subscription));
    }
}