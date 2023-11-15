<?php

declare(strict_types=1);

namespace App\Service\Lookup\Processor;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Lookup\LookupProcessorName;
use App\Service\Feedback\FeedbackSearchSearcher;

class SearchLookupProcessor implements LookupProcessorInterface
{
    public function __construct(
        private readonly FeedbackSearchSearcher $feedbackSearchSearcher,
    )
    {
    }

    public function getName(): LookupProcessorName
    {
        return LookupProcessorName::searches;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        return true;
    }

    public function getSearchers(FeedbackSearchTerm $searchTerm, array $context = []): iterable
    {
        yield fn (FeedbackSearchTerm $searchTerm, array $context = []) => [
            $this->feedbackSearchSearcher
                ->searchFeedbackSearches($searchTerm, withUsers: $context['addTime'] ?? false),
        ];
    }
}
