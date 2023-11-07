<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\FeedbackSearch;

class FeedbackSearchSearchTermUsersNotifier
{
    public function __construct(
        private readonly FeedbackSearchSearchTermUsersNotifierRegistry $feedbackSearchSearchTermUsersNotifierRegistry,
    )
    {
    }

    public function notifyFeedbackSearchSearchTermUsers(FeedbackSearch $feedbackSearch): void
    {
        foreach ($this->feedbackSearchSearchTermUsersNotifierRegistry->getFeedbackSearchSearchTermUserNotifiers() as $notifier) {
            $notifier->notifyFeedbackSearchSearchTermUser($feedbackSearch);
        }
    }
}