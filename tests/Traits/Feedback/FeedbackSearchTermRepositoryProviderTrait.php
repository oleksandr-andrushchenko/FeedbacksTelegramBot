<?php

declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Repository\Feedback\FeedbackSearchTermRepository;

trait FeedbackSearchTermRepositoryProviderTrait
{
    public function getFeedbackSearchTermRepository(): FeedbackSearchTermRepository
    {
        return static::getContainer()->get('app.feedback_search_term_repository');
    }
}