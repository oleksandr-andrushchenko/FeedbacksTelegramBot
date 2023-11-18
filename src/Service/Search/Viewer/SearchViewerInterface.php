<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;

interface SearchViewerInterface
{
    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string;

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string;

    public function getErrorResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string;

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string;
}
