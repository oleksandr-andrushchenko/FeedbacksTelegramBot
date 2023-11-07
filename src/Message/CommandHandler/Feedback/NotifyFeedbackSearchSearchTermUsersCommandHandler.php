<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackSearchSearchTermUsersCommand;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Service\Feedback\Notify\FeedbackSearchSearchTermUsersNotifier;
use Psr\Log\LoggerInterface;

class NotifyFeedbackSearchSearchTermUsersCommandHandler
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackSearchSearchTermUsersNotifier $feedbackSearchSearchTermUsersNotifier,
    )
    {
    }

    public function __invoke(NotifyFeedbackSearchSearchTermUsersCommand $command): void
    {
        $search = $command->getFeedbackSearch() ?? $this->feedbackSearchRepository->find($command->getFeedbackSearchId());

        if ($search === null) {
            $this->logger->warning(sprintf('No feedback search was found in %s for %s id', __CLASS__, $command->getFeedbackSearchId()));
            return;
        }

        $this->feedbackSearchSearchTermUsersNotifier->notifyFeedbackSearchSearchTermUsers($search);
    }
}