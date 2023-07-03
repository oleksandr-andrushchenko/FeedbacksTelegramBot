<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Object\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;

class EmailSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return filter_var($this->normalizeEmail($searchTerm->getText()), FILTER_VALIDATE_EMAIL) !== false;
        }

        if ($searchTerm->getType() === SearchTermType::email) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        $normalized = $this->normalizeEmail($searchTerm->getText());

        if ($normalized === $searchTerm->getText()) {
            $searchTerm
                ->setType(SearchTermType::email)
            ;
        } else {
            $searchTerm
                ->addPossibleType(SearchTermType::email)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === SearchTermType::email) {
            $normalized = $this->normalizeEmail($searchTerm->getText());

            $searchTerm
                ->setNormalizedText($normalized === $searchTerm->getText() ? null : $normalized)
            ;
        }
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithNetwork() method.
    }

    private function normalizeEmail(string $email)
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}