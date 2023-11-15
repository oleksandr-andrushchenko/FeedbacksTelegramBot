<?php

declare(strict_types=1);

namespace App\Service\Lookup\Processor;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Lookup\LookupProcessorName;

interface LookupProcessorInterface
{
    public function getName(): LookupProcessorName;

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool;

    public function getSearchers(FeedbackSearchTerm $searchTerm, array $context = []): iterable;
}
