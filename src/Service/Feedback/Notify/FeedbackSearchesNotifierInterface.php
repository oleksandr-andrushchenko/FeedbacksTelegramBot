<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;

interface FeedbackSearchesNotifierInterface
{
    public function notifyFeedbackSearchUser(FeedbackSearchTerm $searchTerm, Feedback $feedback): void;
}