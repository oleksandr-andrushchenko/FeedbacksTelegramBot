<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;

interface SearchViewerInterface
{
    public function getOnSearchMessage(FeedbackSearchTerm $searchTerm, array $context = []): string;

    public function showLimits(): bool;

    public function getLimitsMessage(): string;

    public function getEmptyMessage(FeedbackSearchTerm $searchTerm, array $context = [], bool $good = null): string;

    public function getErrorMessage(FeedbackSearchTerm $searchTerm, array $context = []): string;

    public function getResultMessage($record, FeedbackSearchTerm $searchTerm, array $context = []): string;
}
