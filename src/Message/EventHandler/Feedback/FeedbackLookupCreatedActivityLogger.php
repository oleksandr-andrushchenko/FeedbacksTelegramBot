<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Message\Event\Feedback\FeedbackLookupCreatedEvent;
use App\Repository\Feedback\FeedbackLookupRepository;
use Psr\Log\LoggerInterface;

class FeedbackLookupCreatedActivityLogger
{
    public function __construct(
        private readonly FeedbackLookupRepository $lookupRepository,
        private readonly FeedbackCommandOptions $options,
        private readonly LoggerInterface $activityLogger,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(FeedbackLookupCreatedEvent $event): void
    {
        if (!$this->options->shouldLogActivities()) {
            return;
        }

        $lookup = $event->getLookup() ?? $this->lookupRepository->find($event->getLookupId());

        if ($lookup === null) {
            $this->logger->warning(sprintf('No feedback lookup was found in %s for %s id', __CLASS__, $event->getLookupId()));
            return;
        }

        $this->activityLogger->info($lookup);
    }
}