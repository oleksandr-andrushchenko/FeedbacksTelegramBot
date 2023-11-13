<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

class ClarityProjectEdrsRecord
{
    public function __construct(
        /**
         * @var ClarityProjectEdr[]
         */
        private array $edrs = []
    )
    {
    }

    public function getEdrs(): array
    {
        return $this->edrs;
    }

    public function addEdr(ClarityProjectEdr $edr): self
    {
        $this->edrs[] = $edr;

        return $this;
    }
}