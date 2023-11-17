<?php

declare(strict_types=1);

namespace App\Entity\Search\Clarity;

class ClarityPersonCourtsRecord
{
    public function __construct(
        /**
         * @var ClarityPersonCourt[]
         */
        private array $courts = []
    )
    {
    }

    public function getCourts(): array
    {
        return $this->courts;
    }

    public function addCourt(ClarityPersonCourt $court): self
    {
        $this->courts[] = $court;

        return $this;
    }
}