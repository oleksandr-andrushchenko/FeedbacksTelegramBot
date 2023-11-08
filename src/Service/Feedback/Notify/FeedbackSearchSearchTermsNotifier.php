<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\FeedbackSearch;

class FeedbackSearchSearchTermsNotifier
{
    public function __construct(
        private readonly FeedbackSearchSearchTermsNotifierRegistry $feedbackSearchSearchTermUsersNotifierRegistry,
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