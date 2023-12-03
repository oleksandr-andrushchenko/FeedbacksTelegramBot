<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;

abstract class SearchViewer implements SearchViewerInterface
{
    public function __construct(
        protected readonly SearchViewerHelper $searchViewerHelper,
    )
    {
    }

    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->searchViewerHelper->getOnSearchTitle();
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = [], bool $good = null): string
    {
        $message = '';

        if ($good) {
            // ✅☑️☀️👍🟢✔️
            $message .= '☑️ ';
        }

        $message .= $this->searchViewerHelper->getEmptyResultTitle();

        return $message;
    }

    public function getErrorResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->searchViewerHelper->getErrorResultTitle();
    }
}
