<?php

declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Repository\Feedback\FeedbackSearchSearchRepository;

trait FeedbackSearchSearchRepositoryProviderTrait
{
    public function getFeedbackSearchSearchRepository(): FeedbackSearchSearchRepository
    {
        return static::getContainer()->get('app.feedback_search_search_repository');
    }
}