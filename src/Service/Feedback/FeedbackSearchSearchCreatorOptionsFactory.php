<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearchSearchCreatorOptions;

class FeedbackSearchSearchCreatorOptionsFactory
{
    public static function createFeedbackSearchSearchCreatorOptions(array $options): FeedbackSearchSearchCreatorOptions
    {
        return new FeedbackSearchSearchCreatorOptions(
            $options['log_activities'],
        );
    }
}