<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Search\SearchProviderName;
use App\Service\Feedback\FeedbackSearcher;

class FeedbackSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private readonly FeedbackSearcher $feedbackSearcher,
    )
    {
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::feedbacks;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        return true;
    }

    public function getSearcher(FeedbackSearchTerm $searchTerm, array $context = []): ?callable
    {
        return fn (): array => [
            $this->feedbackSearcher->searchFeedbacks($searchTerm, withUsers: $context['addTime'] ?? false),
        ];
    }
}
