<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Search\SearchProviderName;
use App\Service\Feedback\FeedbackSearchSearcher;

class SearchRegistrySearchProvider extends SearchProvider implements SearchProviderInterface
{
    public function __construct(
        SearchProviderCompose $searchProviderCompose,
        private readonly FeedbackSearchSearcher $feedbackSearchSearcher,
    )
    {
        parent::__construct($searchProviderCompose);
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::searches;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        return true;
    }

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array
    {
        return [
            $this->feedbackSearchSearcher->searchFeedbackSearches($searchTerm, withUsers: $context['addTime'] ?? false),
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return null;
    }
}
