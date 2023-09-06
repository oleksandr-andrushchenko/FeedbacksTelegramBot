<?php

declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Service\Feedback\SearchTerm\FeedbackSearchTermTypeProvider;

trait FeedbackSearchTermTypeProviderTrait
{
    public function getFeedbackSearchTermTypeProvider(): FeedbackSearchTermTypeProvider
    {
        return static::getContainer()->get('app.feedback_search_term_type_provider');
    }
}