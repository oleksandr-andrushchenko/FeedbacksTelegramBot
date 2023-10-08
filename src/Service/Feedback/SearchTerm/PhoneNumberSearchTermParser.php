<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;

class PhoneNumberSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supports($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::phone_number) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        if ($this->supports($searchTerm->getText())) {
            $normalized = $this->normalize($searchTerm->getText());

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
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === SearchTermType::phone_number) {
            $normalized = $this->normalize($searchTerm->getText());

            $searchTerm
                ->setNormalizedText($normalized === $searchTerm->getText() ? null : $normalized)
            ;
        }
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithNetwork() method.
    }

    private function supports(string $number): bool
    {
        return preg_match("/^\\+?\\d{1,4}?[-.\\s]?\\(?\\d{1,3}?\\)?[-.\\s]?\\d{1,4}[-.\\s]?\\d{1,4}[-.\\s]?\\d{1,9}$/", $number) === 1;
    }

    private function normalize(string $number): ?string
    {
        return preg_replace('/[^0-9]/', '', $number);
    }
}