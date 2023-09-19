<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Object\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;

class CarNumberSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supports($searchTerm->getText());
        }

        if ($searchTerm->getText() === SearchTermType::car_number) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        if ($this->supports($searchTerm->getText())) {
            $searchTerm
                ->addPossibleType(SearchTermType::car_number)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithKnownType() method.
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithNetwork() method.
    }

    private function supports(string $number): bool
    {
        if (preg_match('/[0-9]+/', $number) === 0) {
            return false;
        }

        $result = preg_match($this->getPattern($first = 'A-Za-z0-9\pL\x{00C0}-\x{00FF}', $first), $number);

        if ($result === 1) {
            return true;
        }

        return false;
    }

    private function getPattern(string $first, string $next): string
    {
        return sprintf('/^[%s][%s\-]+([\ %s][%s\-]+)*$/iu', $first, ...array_fill(0, 3, $next));
    }
}
