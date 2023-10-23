<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Event\Feedback\FeedbackUserSubscriptionCreatedEvent;
use App\Repository\Feedback\FeedbackUserSubscriptionRepository;
use Psr\Log\LoggerInterface;

class FeedbackUserSubscriptionCreatedActivityLogger
{
    public function __construct(
        private readonly FeedbackUserSubscriptionRepository $subscriptionRepository,
        private readonly LoggerInterface $activityLogger,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(FeedbackUserSubscriptionCreatedEvent $event): void
    {
        $subscription = $event->getSubscription() ?? $this->subscriptionRepository->find($event->getSubscriptionId());

        if ($subscription === null) {
            $this->logger->warning(sprintf('No feedback user subscription was found in %s for %s id', __CLASS__, $event->getSubscriptionId()));
            return;
        }

        $this->activityLogger->info($subscription);
    }
}