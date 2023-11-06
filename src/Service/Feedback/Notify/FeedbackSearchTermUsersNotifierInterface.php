<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;

interface FeedbackSearchTermUsersNotifierInterface
{
    public function notifyFeedbackSearchTermUser(FeedbackSearchTerm $searchTerm, Feedback $feedback): void;
}