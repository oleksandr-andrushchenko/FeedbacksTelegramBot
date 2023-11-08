<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackLookupsCommand;
use App\Message\Command\Feedback\NotifyFeedbackSearchSearchTermsCommand;
use App\Message\Command\NotifyActivityAdminsCommand;
use App\Message\Event\Feedback\FeedbackSearchCreatedEvent;
use App\Repository\Feedback\FeedbackSearchRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSearchCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackSearchCreatedEvent $event): void
    {
        $search = $event->getFeedbackSearch() ?? $this->feedbackSearchRepository->find($event->getFeedbackSearchId());

        if ($search === null) {
            $this->logger->warning(sprintf('No feedback search was found in %s for %s id', __CLASS__, $event->getFeedbackSearchId()));
            return;
        }

        // notify: somebody made a feedback search, for admin
        $this->commandBus->dispatch(new NotifyActivityAdminsCommand(entity: $search));
        // notify: somebody has been searching for feedbacks on you
        $this->commandBus->dispatch(new NotifyFeedbackSearchSearchTermsCommand(search: $search));
        // notify: you've been looking for such search requests
        $this->commandBus->dispatch(new NotifyFeedbackLookupsCommand(search: $search));
        // todo: notify other feedback search users about same search
    }
}