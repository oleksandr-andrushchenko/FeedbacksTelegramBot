<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

class ClarityProjectEnforcementsRecord
{
    public function __construct(
        /**
         * @var ClarityProjectEnforcement[]
         */
        private array $enforcements = []
    )
    {
    }

    public function getEnforcements(): array
    {
        return $this->enforcements;
    }

    public function addEnforcement(ClarityProjectEnforcement $enforcement): self
    {
        $this->enforcements[] = $enforcement;

        return $this;
    }
}