<?php

declare(strict_types=1);

namespace App\Service\Lookup\Processor;

use App\Entity\Feedback\FeedbackSearch;
use App\Enum\Lookup\LookupProcessorName;
use App\Service\Feedback\FeedbackSearcher;

class FeedbackLookupProcessor implements LookupProcessorInterface
{
    public function __construct(
        private readonly FeedbackSearcher $feedbackSearcher,
    )
    {
    }

    public function getName(): LookupProcessorName
    {
        return LookupProcessorName::feedbacks_registry;
    }

    public function supports(FeedbackSearch $feedbackSearch, array $context = []): bool
    {
        return true;
    }

    public function search(FeedbackSearch $feedbackSearch, array $context = []): array
    {
        return $this->feedbackSearcher->searchFeedbacks(
            $feedbackSearch->getSearchTerm(),
            withUsers: $context['addTime'] ?? false
        );
    }
}
