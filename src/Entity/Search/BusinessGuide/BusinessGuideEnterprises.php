<?php

declare(strict_types=1);

namespace App\Entity\Search\BusinessGuide;

class BusinessGuideEnterprises
{
    public function __construct(
        private array $items = []
    )
    {
    }

    /**
     * @return BusinessGuideEnterprise[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(BusinessGuideEnterprise $item): void
    {
        $this->items[] = $item;
    }
}