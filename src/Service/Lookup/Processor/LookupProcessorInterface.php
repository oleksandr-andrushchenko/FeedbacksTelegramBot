<?php

declare(strict_types=1);

namespace App\Service\Lookup\Processor;

use App\Entity\Feedback\FeedbackSearch;
use App\Enum\Lookup\LookupProcessorName;

interface LookupProcessorInterface
{
    public function getName(): LookupProcessorName;

    public function supports(FeedbackSearch $feedbackSearch, array $context = []): bool;

    /**
     * @param FeedbackSearch $feedbackSearch
     * @param array $context
     * @return array
     */
    public function search(FeedbackSearch $feedbackSearch, array $context = []): array;
}
