<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\FeedbackSearch;

class FeedbackLookupsNotifier
{
    public function __construct(
        private readonly FeedbackLookupsNotifierRegistry $feedbackLookupUsersNotifierRegistry,
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