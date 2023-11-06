<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\Feedback;

class FeedbackSearchUsersNotifier
{
    public function __construct(
        private readonly FeedbackSearchUsersNotifierRegistry $feedbackSearchUsersNotifierRegistry,
    )
    {
    }

    public function notifyFeedbackSearchUsers(Feedback $feedback): void
    {
        foreach ($feedback->getSearchTerms() as $searchTerm) {
            foreach ($this->feedbackSearchUsersNotifierRegistry->getFeedbackSearchUserNotifiers() as $notifier) {
                $notifier->notifyFeedbackSearchUser($searchTerm, $feedback);
            }
        }
    }
}