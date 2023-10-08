<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;

class SearchTermParser implements SearchTermParserInterface
{
    public function __construct(
        private readonly SearchTermParserFactory $parserFactory,
    )
    {
    }

    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        foreach ($this->parserFactory->createSearchTermParsers() as $parser) {
            if ($parser->supportsSearchTerm($searchTerm)) {
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

        foreach ($this->parserFactory->createSearchTermParsers() as $parser) {
            if ($parser->supportsSearchTerm($searchTerm)) {
                $parser->parseWithGuessType($searchTerm);
            }
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === null) {
            return;
        }

        foreach ($this->parserFactory->createSearchTermParsers() as $parser) {
            if ($parser->supportsSearchTerm($searchTerm)) {
                $parser->parseWithKnownType($searchTerm);
            }
        }
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === null) {
            return;
        }

        foreach ($this->parserFactory->createSearchTermParsers() as $parser) {
            if ($parser->supportsSearchTerm($searchTerm)) {
                $parser->parseWithNetwork($searchTerm);
            }
        }
    }
}
