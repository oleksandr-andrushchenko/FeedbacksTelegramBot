<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

class ClarityEdrsRecord
{
    public function __construct(
        /**
         * @var ClarityEdr[]
         */
        private array $edrs = []
    )
    {
    }

    public function getEdrs(): array
    {
        return $this->edrs;
    }

    public function addEdr(ClarityEdr $edr): self
    {
        $this->edrs[] = $edr;

        return $this;
    }
}