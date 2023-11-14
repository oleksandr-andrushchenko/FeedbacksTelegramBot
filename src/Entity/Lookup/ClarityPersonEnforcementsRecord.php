<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

class ClarityPersonEnforcementsRecord
{
    public function __construct(
        /**
         * @var ClarityPersonEnforcement[]
         */
        private array $enforcements = []
    )
    {
    }

    public function getEnforcements(): array
    {
        return $this->enforcements;
    }

    public function addEnforcement(ClarityPersonEnforcement $enforcement): self
    {
        $this->enforcements[] = $enforcement;

        return $this;
    }
}