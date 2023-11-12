<?php

declare(strict_types=1);

namespace App\Service\Lookup\Viewer;

use App\Entity\Feedback\FeedbackSearch;

interface LookupViewerInterface
{
    public function getOnSearchTitle(FeedbackSearch $feedbackSearch, array $context = []): string;

    public function getEmptyResultTitle(FeedbackSearch $feedbackSearch, array $context = []): string;

    public function getResultTitle(FeedbackSearch $feedbackSearch, int $count, array $context = []): string;

    public function getResultRecord($record, array $context = []): string;
}
