<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbacksCommand;
use App\Message\Command\Feedback\NotifyFeedbackSearchesCommand;
use App\Message\Command\Feedback\NotifyFeedbackSearchTermsCommand;
use App\Message\Command\NotifyActivityAdminsCommand;
use App\Message\Event\Feedback\FeedbackCreatedEvent;
use App\Repository\Feedback\FeedbackRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackCreatedEvent $event): void
    {
        $feedback = $event->getFeedback() ?? $this->feedbackRepository->find($event->getFeedbackId());

        if ($feedback === null) {
            $this->logger->warning(sprintf('No feedback was found in %s for %s id', __CLASS__, $event->getFeedbackId()));
            return;
        }

        // notify: somebody left a feedback for admin
        $this->commandBus->dispatch(new NotifyActivityAdminsCommand(entity: $feedback));
        // notify: somebody left a feedback on you
        $this->commandBus->dispatch(new NotifyFeedbackSearchTermsCommand(feedback: $feedback));
        // notify: somebody left a feedback on what you've been looking for
        $this->commandBus->dispatch(new NotifyFeedbackSearchesCommand(feedback: $feedback));
        // notify: somebody left a feedback on the same thing you already did
        $this->commandBus->dispatch(new NotifyFeedbacksCommand(feedback: $feedback));
    }
}