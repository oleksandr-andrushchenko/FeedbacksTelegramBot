<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;

interface SearchTermSupportsInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool;
}
