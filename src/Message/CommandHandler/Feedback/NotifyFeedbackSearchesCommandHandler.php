<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackSearchesCommand;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\Feedback\Notify\FeedbackSearchesNotifier;
use Psr\Log\LoggerInterface;

class NotifyFeedbackSearchesCommandHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackSearchesNotifier $feedbackSearchUsersNotifier,
    )
    {
    }

    public function __invoke(NotifyFeedbackSearchesCommand $command): void
    {
        $feedback = $command->getFeedback() ?? $this->feedbackRepository->find($command->getFeedbackId());

        if ($feedback === null) {
            $this->logger->warning(sprintf('No feedback was found in %s for %s id', __CLASS__, $command->getFeedbackId()));
            return;
        }

        $this->feedbackSearchUsersNotifier->notifyFeedbackSearchUsers($feedback);
    }
}