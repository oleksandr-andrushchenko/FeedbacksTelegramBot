<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;

class UrlSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return !empty(parse_url($searchTerm->getText(), PHP_URL_HOST));
        }

        if (in_array($searchTerm->getType(), [SearchTermType::url, SearchTermType::messenger_profile_url], true)) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        $searchTerm
            ->addType(SearchTermType::messenger_profile_url)
            ->addType(SearchTermType::url)
        ;
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
    }
}