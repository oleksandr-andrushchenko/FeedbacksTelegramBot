<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;

/**
 * @todo split into several interfaces
 */
interface SearchTermParserOnlyInterface
{
    /**
     * Set search term type if 100% match, otherwise - set possible types
     *
     * @param SearchTermTransfer $searchTerm
     */
    public function parseWithGuessType(SearchTermTransfer $searchTerm): void;

    /**
     * Set search term info according to known type, from search term itself
     *
     * @param SearchTermTransfer $searchTerm
     */
    public function parseWithKnownType(SearchTermTransfer $searchTerm): void;
}
