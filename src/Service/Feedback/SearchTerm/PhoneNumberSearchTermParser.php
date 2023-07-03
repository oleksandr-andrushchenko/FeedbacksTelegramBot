<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Object\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;

class PhoneNumberSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return preg_match("/^\\+?\\d{1,4}?[-.\\s]?\\(?\\d{1,3}?\\)?[-.\\s]?\\d{1,4}[-.\\s]?\\d{1,4}[-.\\s]?\\d{1,9}$/", $searchTerm->getText()) === 1;
        }

        if ($searchTerm->getType() === SearchTermType::phone_number) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        $normalized = $this->normalizePhoneNumber($searchTerm->getText());

        if ($normalized === $searchTerm->getText()) {
            $searchTerm
                ->setType(SearchTermType::phone_number)
            ;
        } else {
            $searchTerm
                ->addPossibleType(SearchTermType::phone_number)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === SearchTermType::phone_number) {
            $normalized = $this->normalizePhoneNumber($searchTerm->getText());

            $searchTerm
                ->setNormalizedText($normalized === $searchTerm->getText() ? null : $normalized)
            ;
        }
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithNetwork() method.
    }

    private function normalizePhoneNumber(string $phoneNumber): ?string
    {
//        return filter_var($phoneNumber, FILTER_SANITIZE_NUMBER_INT);
        return preg_replace('/[^0-9]+/', '', $phoneNumber);
    }
}