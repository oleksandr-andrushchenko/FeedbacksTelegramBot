<?php

declare(strict_types=1);

namespace App\Entity\Lookup\Clarity;

class ClarityPersonDebtorsRecord
{
    public function __construct(
        /**
         * @var ClarityPersonDebtor[]
         */
        private array $debtors = []
    )
    {
    }

    public function getDebtors(): array
    {
        return $this->debtors;
    }

    public function addDebtor(ClarityPersonDebtor $debtor): self
    {
        $this->debtors[] = $debtor;

        return $this;
    }
}