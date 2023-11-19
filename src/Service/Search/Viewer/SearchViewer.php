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
        return $this->searchViewerHelper->trans('on_search');
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->searchViewerHelper->trans('empty_result', generalDomain: true);
    }

    public function getErrorResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->searchViewerHelper->trans('error_result', generalDomain: true);
    }
}
