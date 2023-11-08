<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;

class UnknownMessengerSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supportsUsername($searchTerm);
        }

        if ($searchTerm->getType() === SearchTermType::messenger_username) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        $searchTerm
            ->addType(SearchTermType::messenger_username)
        ;
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($searchTerm->getType() === SearchTermType::messenger_username) {
            $normalizedUsername = $this->normalizeUsername($searchTerm->getText());

            if ($normalizedUsername !== $searchTerm->getText()) {
                $searchTerm
                    ->setNormalizedText($normalizedUsername)
                ;
            }
        }
    }

    private function supportsUsername(SearchTermTransfer $searchTerm): bool
    {
        return preg_match('/^' . $this->getUsernamePattern() . '$/im', $searchTerm->getText()) === 1;
    }

    private function getUsernamePattern(): string
    {
        return '@?[A-Za-z0-9-_\.]+';
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }
}