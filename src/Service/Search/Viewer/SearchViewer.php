<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

abstract class SearchViewer
{
    public function __construct(
        protected readonly SearchViewerHelper $searchViewerHelper,
    )
    {
    }
}
