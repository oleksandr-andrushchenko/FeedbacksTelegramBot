<?php

declare(strict_types=1);

namespace App\Entity;

readonly class Money
{
    public function __construct(
        private float $amount,
        private string $currency
    )
    {
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
