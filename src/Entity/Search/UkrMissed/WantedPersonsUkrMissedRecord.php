<?php

declare(strict_types=1);

namespace App\Entity\Search\UkrMissed;

class WantedPersonsUkrMissedRecord
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

    public function addItem(UkrMissedPerson $item): void
    {
        $this->items[] = $item;
    }
}