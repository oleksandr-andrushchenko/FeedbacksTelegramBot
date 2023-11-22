<?php

declare(strict_types=1);

namespace App\Entity\Search\Clarity;

class ClarityPersonsRecord
{
    public function __construct(
        private array $items = []
    )
    {
    }

    /**
     * @return ClarityPerson[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(ClarityPerson $item): void
    {
        $this->items[] = $item;
    }
}