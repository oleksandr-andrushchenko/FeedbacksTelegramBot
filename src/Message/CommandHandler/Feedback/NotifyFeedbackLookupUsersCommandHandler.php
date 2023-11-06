<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackLookupUsersCommand;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Service\Feedback\Notify\FeedbackLookupUsersNotifier;
use Psr\Log\LoggerInterface;

class NotifyFeedbackLookupUsersCommandHandler
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackLookupUsersNotifier $feedbackLookupUsersNotifier,
    )
    {
    }

    public function __invoke(NotifyFeedbackLookupUsersCommand $command): void
    {
        $search = $command->getFeedbackSearch() ?? $this->feedbackSearchRepository->find($command->getFeedbackSearchId());

        if ($search === null) {
            $this->logger->warning(sprintf('No feedback search was found in %s for %s id', __CLASS__, $command->getFeedbackSearchId()));
            return;
        }

        $this->feedbackLookupUsersNotifier->notifyFeedbackLookupUsers($search);
    }
}