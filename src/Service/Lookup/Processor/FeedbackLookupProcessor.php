<?php

declare(strict_types=1);

namespace App\Service\Lookup\Processor;

use App\Entity\Feedback\FeedbackSearchTerm;
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
        return LookupProcessorName::feedbacks;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        return true;
    }

    public function getSearchers(FeedbackSearchTerm $searchTerm, array $context = []): iterable
    {
        yield fn () => [
            $this->feedbackSearcher->searchFeedbacks($searchTerm, withUsers: $context['addTime'] ?? false),
        ];
    }
}
