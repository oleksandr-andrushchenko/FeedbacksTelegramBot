<?php

declare(strict_types=1);

namespace App\Entity\Search\Otzyvua;

class OtzyvuaFeedbackSearchTermsRecord
{
    public function __construct(
        private array $items = []
    )
    {
    }

    /**
     * @return OtzyvuaFeedbackSearchTerm[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function addItem(OtzyvuaFeedbackSearchTerm $item): void
    {
        $this->items[] = $item;
    }
}