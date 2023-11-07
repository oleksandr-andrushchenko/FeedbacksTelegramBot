<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackSearchUsersCommand;
use App\Message\Command\Feedback\NotifyFeedbackSearchTermUsersCommand;
use App\Message\Command\NotifyActivityReceiversCommand;
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

        $this->commandBus->dispatch(new NotifyActivityReceiversCommand(entity: $feedback));
        $this->commandBus->dispatch(new NotifyFeedbackSearchTermUsersCommand(feedback: $feedback));
        $this->commandBus->dispatch(new NotifyFeedbackSearchUsersCommand(feedback: $feedback));
        // todo: notify other feedback users about same creation (feedback on the same target user)
    }
}