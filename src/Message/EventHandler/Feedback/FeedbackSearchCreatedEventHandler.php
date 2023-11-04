<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Message\Event\Feedback\FeedbackSearchCreatedEvent;
use App\Repository\Feedback\FeedbackSearchRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class FeedbackSearchCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackSearchRepository $searchRepository,
        private readonly FeedbackCommandOptions $options,
        private readonly LoggerInterface $activityLogger,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(FeedbackSearchCreatedEvent $event): void
    {
        if (!$this->options->shouldLogActivities()) {
            return;
        }

        $search = $event->getSearch() ?? $this->searchRepository->find($event->getSearchId());

        if ($search === null) {
            $this->logger->warning(sprintf('No feedback search was found in %s for %s id', __CLASS__, $event->getSearchId()));
            return;
        }

        try {
            $this->activityLogger->info($search);
        } catch (Throwable $exception) {
            $this->logger->error($exception);
        }
    }
}