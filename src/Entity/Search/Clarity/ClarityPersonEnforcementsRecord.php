<?php

declare(strict_types=1);

namespace App\Entity\Search\Clarity;

class ClarityPersonEnforcementsRecord
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

    public function addItem(ClarityPersonEnforcement $item): void
    {
        $this->items[] = $item;
    }
}