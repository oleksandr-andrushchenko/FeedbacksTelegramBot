<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;

class SearchTermParser implements SearchTermParserInterface
{
    public function __construct(
        private readonly SearchTermParserFactory $searchTermParserFactory,
    )
    {
    }

    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        foreach ($this->searchTermParserFactory->createSearchTermParsers() as $searchTermParser) {
            if ($searchTermParser->supportsSearchTerm($searchTerm)) {
                return true;
            }
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() !== null) {
            return;
        }

        foreach ($this->searchTermParserFactory->createSearchTermParsers() as $searchTermParser) {
            if ($searchTermParser->supportsSearchTerm($searchTerm)) {
                $searchTermParser->parseWithGuessType($searchTerm);
            }
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === null) {
            return;
        }

        foreach ($this->searchTermParserFactory->createSearchTermParsers() as $searchTermParser) {
            if ($searchTermParser->supportsSearchTerm($searchTerm)) {
                $searchTermParser->parseWithKnownType($searchTerm);
            }
        }
    }
}
