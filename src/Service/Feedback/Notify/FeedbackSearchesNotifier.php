<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\Feedback;

class FeedbackSearchesNotifier
{
    public function __construct(
        private readonly FeedbackSearchesNotifierRegistry $feedbackSearchUsersNotifierRegistry,
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