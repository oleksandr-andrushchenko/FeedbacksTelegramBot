<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;

class CarNumberSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supports($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::car_number) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        if ($this->supports($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::car_number)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === SearchTermType::car_number) {
            $normalized = $this->normalize($searchTerm->getText());

            if ($normalized !== $searchTerm->getText()) {
                $searchTerm
                    ->setNormalizedText($normalized)
                ;
            }
        }
    }

    private function supports(string $number): bool
    {
        $result = preg_match($this->getPattern($first = 'A-Za-z0-9\pL\x{00C0}-\x{00FF}', $first . '\ '), $number);

        if ($result === 1) {
            return true;
        }

        return false;
    }

    private function getPattern(string $first, string $next): string
    {
        return sprintf('/^[%s][%s\-]+([\ %s][%s\-]+)*$/iu', $first, ...array_fill(0, 3, $next));
    }

    private function normalize(string $number): ?string
    {
        return preg_replace('/[^\p{L}0-9]/u', '', $number);
    }
}
