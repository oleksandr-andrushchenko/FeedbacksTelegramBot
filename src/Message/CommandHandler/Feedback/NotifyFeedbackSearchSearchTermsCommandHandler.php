<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackSearchSearchTermsCommand;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Service\Feedback\Notify\FeedbackSearchSearchTermsNotifier;
use Psr\Log\LoggerInterface;

class NotifyFeedbackSearchSearchTermsCommandHandler
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackSearchSearchTermsNotifier $feedbackSearchSearchTermUsersNotifier,
    )
    {
    }

    public function __invoke(NotifyFeedbackSearchSearchTermsCommand $command): void
    {
        $search = $command->getFeedbackSearch() ?? $this->feedbackSearchRepository->find($command->getFeedbackSearchId());

        if ($search === null) {
            $this->logger->warning(sprintf('No feedback search was found in %s for %s id', __CLASS__, $command->getFeedbackSearchId()));
            return;
        }

        $this->feedbackSearchSearchTermUsersNotifier->notifyFeedbackSearchSearchTermUsers($search);
    }
}