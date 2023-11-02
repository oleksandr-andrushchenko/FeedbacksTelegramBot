<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Message\Event\Feedback\FeedbackCreatedEvent;
use App\Repository\Feedback\FeedbackRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class FeedbackCreatedActivityLogger
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly FeedbackCommandOptions $options,
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

        try {
            $this->activityLogger->info($feedback);
        } catch (Throwable $exception) {
            $this->logger->error($exception);
        }
    }
}