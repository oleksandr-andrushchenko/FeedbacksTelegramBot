<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackCreatorOptions;

class FeedbackCreatorOptionsFactory
{
    public static function createFeedbackCreatorOptions(array $options): FeedbackCreatorOptions
    {
        return new FeedbackCreatorOptions(
            $options['user_target_messenger_required'],
            $options['user_per_day_limit'],
            $options['user_per_month_limit'],
            $options['user_per_year_limit'],
        );
    }
}