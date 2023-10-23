<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Entity\CommandOptions;
use App\Message\Event\Feedback\FeedbackSearchCreatedEvent;
use App\Repository\Feedback\FeedbackSearchRepository;
use Psr\Log\LoggerInterface;

class FeedbackSearchCreatedActivityLogger
{
    public function __construct(
        private readonly FeedbackSearchRepository $searchRepository,
        private readonly CommandOptions $options,
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

        $this->activityLogger->info($search);
    }
}