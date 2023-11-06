<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\Feedback;

class FeedbackSearchTermUsersNotifier
{
    public function __construct(
        private readonly FeedbackSearchTermUsersNotifierRegistry $feedbackSearchTermUserNotifierRegistry,
    )
    {
    }

    public function notifyFeedbackSearchTermUsers(Feedback $feedback): void
    {
        foreach ($feedback->getSearchTerms() as $searchTerm) {
            foreach ($this->feedbackSearchTermUserNotifierRegistry->getFeedbackSearchTermUserNotifiers() as $notifier) {
                $notifier->notifyFeedbackSearchTermUser($searchTerm, $feedback);
            }
        }
    }
}