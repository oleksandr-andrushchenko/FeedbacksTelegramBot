<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearchCreatorOptions;

class FeedbackSearchCreatorOptionsFactory
{
    public static function createFeedbackSearchCreatorOptions(array $options): FeedbackSearchCreatorOptions
    {
        return new FeedbackSearchCreatorOptions(
            $options['user_per_day_limit'],
            $options['user_per_month_limit'],
            $options['user_per_year_limit'],
        );
    }
}