<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;

abstract class SearchViewer
{
    public function __construct(
        protected readonly SearchViewerHelper $searchViewerHelper,
    )
    {
    }

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->searchViewerHelper->trans('empty_result', generalDomain: true);
    }
}
