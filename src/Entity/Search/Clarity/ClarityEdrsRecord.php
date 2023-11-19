<?php

declare(strict_types=1);

namespace App\Entity\Search\Clarity;

class ClarityEdrsRecord
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

    public function addItem(ClarityEdr $item): void
    {
        $this->items[] = $item;
    }
}