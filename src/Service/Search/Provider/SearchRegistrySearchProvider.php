<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Search\SearchProviderName;
use App\Service\Feedback\FeedbackSearchSearcher;

class SearchRegistrySearchProvider implements SearchProviderInterface
{
    public function __construct(
        private readonly FeedbackSearchSearcher $feedbackSearchSearcher,
    )
    {
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::searches;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        return true;
    }

    public function getSearchers(FeedbackSearchTerm $searchTerm, array $context = []): iterable
    {
        yield fn () => [
            $this->feedbackSearchSearcher->searchFeedbackSearches($searchTerm, withUsers: $context['addTime'] ?? false),
        ];
    }
}
