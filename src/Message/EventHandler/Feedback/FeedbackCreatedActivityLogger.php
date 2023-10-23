<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Entity\CommandOptions;
use App\Message\Event\Feedback\FeedbackCreatedEvent;
use App\Repository\Feedback\FeedbackRepository;
use Psr\Log\LoggerInterface;

class FeedbackCreatedActivityLogger
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly CommandOptions $options,
        private readonly LoggerInterface $activityLogger,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(FeedbackCreatedEvent $event): void
    {
        if (!$this->options->shouldLogActivities()) {
            return;
        }

        $feedback = $event->getFeedback() ?? $this->feedbackRepository->find($event->getFeedbackId());

        if ($feedback === null) {
            $this->logger->warning(sprintf('No feedback was found in %s for %s id', __CLASS__, $event->getFeedbackId()));
            return;
        }

        $this->activityLogger->info($feedback);
    }
}