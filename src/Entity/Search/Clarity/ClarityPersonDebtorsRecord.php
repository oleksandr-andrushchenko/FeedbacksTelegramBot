<?php

declare(strict_types=1);

namespace App\Entity\Search\Clarity;

class ClarityPersonDebtorsRecord
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

    public function addItem(ClarityPersonDebtor $item): void
    {
        $this->items[] = $item;
    }
}