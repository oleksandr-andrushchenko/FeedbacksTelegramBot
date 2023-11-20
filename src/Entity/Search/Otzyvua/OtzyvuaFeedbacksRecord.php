<?php

declare(strict_types=1);

namespace App\Entity\Search\Otzyvua;

class OtzyvuaFeedbacksRecord
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

    public function addItem(OtzyvuaFeedback $item): void
    {
        $this->items[] = $item;
    }
}