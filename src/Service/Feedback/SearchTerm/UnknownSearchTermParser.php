<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;

class UnknownSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        return true;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        $searchTerm
            ->addPossibleType(SearchTermType::unknown)
        ;
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithKnownType() method.
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithNetwork() method.
    }
}