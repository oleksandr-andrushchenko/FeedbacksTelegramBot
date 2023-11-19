<?php

declare(strict_types=1);

namespace App\Entity\Search\Clarity;

class ClarityPersonSecurityRecord
{
    public function __construct(
        private array $items = []
    )
    {
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(ClarityPersonSecurity $item): void
    {
        $this->items[] = $item;
    }
}