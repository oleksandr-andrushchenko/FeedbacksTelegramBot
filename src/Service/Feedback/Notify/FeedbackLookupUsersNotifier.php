<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\FeedbackSearch;

class FeedbackLookupUsersNotifier
{
    public function __construct(
        private readonly FeedbackLookupUsersNotifierRegistry $feedbackLookupUsersNotifierRegistry,
    )
    {
    }

    public function notifyFeedbackLookupUsers(FeedbackSearch $feedbackSearch): void
    {
        foreach ($this->feedbackLookupUsersNotifierRegistry->getFeedbackLookupUserNotifiers() as $notifier) {
            $notifier->notifyFeedbackLookupUser($feedbackSearch);
        }
    }
}