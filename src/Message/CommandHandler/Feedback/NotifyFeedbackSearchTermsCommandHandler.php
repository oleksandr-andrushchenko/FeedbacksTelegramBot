<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackSearchTermsCommand;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\Feedback\Notify\FeedbackSearchTermsNotifier;
use Psr\Log\LoggerInterface;

class NotifyFeedbackSearchTermsCommandHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackSearchTermsNotifier $feedbackSearchTermUsersNotifier,
    )
    {
    }

    public function __invoke(NotifyFeedbackSearchTermsCommand $command): void
    {
        $feedback = $command->getFeedback() ?? $this->feedbackRepository->find($command->getFeedbackId());

        if ($feedback === null) {
            $this->logger->warning(sprintf('No feedback was found in %s for %s id', __CLASS__, $command->getFeedbackId()));
            return;
        }

        $this->feedbackSearchTermUsersNotifier->notifyFeedbackSearchTermUsers($feedback);
    }
}