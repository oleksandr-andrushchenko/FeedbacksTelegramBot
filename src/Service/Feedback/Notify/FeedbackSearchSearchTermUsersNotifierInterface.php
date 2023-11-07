<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\FeedbackSearch;

interface FeedbackSearchSearchTermUsersNotifierInterface
{
    public function notifyFeedbackSearchSearchTermUser(FeedbackSearch $feedbackSearch): void;
}