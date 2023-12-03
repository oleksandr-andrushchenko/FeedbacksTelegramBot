<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Enum\Search\SearchProviderName;

interface SearchProviderInterface
{
    public function getName(): SearchProviderName;

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool;

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array;

    public function goodOnEmptyResult(): ?bool;
}
