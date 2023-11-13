<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

class ClarityProjectCourtsRecord
{
    public function __construct(
        /**
         * @var ClarityProjectCourt[]
         */
        private array $courts = []
    )
    {
    }

    public function getCourts(): array
    {
        return $this->courts;
    }

    public function addCourt(ClarityProjectCourt $court): self
    {
        $this->courts[] = $court;

        return $this;
    }
}