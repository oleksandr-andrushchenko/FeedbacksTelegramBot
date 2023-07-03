<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\User;
use App\Enum\Feedback\Rating;
use App\Repository\Feedback\FeedbackRepository;

class UserRatingSyncer
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
    )
    {
    }

    public function syncUserRating(User $user): void
    {
        $rating = $this->feedbackRepository->avgRatingByUser($user);

        $user->setRating($rating === null ? null : Rating::from($rating));
    }
}