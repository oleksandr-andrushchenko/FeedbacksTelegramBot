<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackLookupSourcesAboutNewFeedbackSearchCommand;
use App\Message\Command\Feedback\NotifyFeedbackSearchSourcesAboutNewFeedbackSearchCommand;
use App\Message\Command\Feedback\NotifyFeedbackSearchTargetsAboutNewFeedbackSearchCommand;
use App\Message\Command\NotifyAdminAboutNewActivityCommand;
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

        $this->commandBus->dispatch(new NotifyAdminAboutNewActivityCommand(entity: $search));
        $this->commandBus->dispatch(new NotifyFeedbackSearchTargetsAboutNewFeedbackSearchCommand(search: $search));
        $this->commandBus->dispatch(new NotifyFeedbackLookupSourcesAboutNewFeedbackSearchCommand(search: $search));
        $this->commandBus->dispatch(new NotifyFeedbackSearchSourcesAboutNewFeedbackSearchCommand(search: $search));
    }
}