<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

class ClarityProjectSecurityRecord
{
    public function __construct(
        /**
         * @var ClarityProjectSecurity[]
         */
        private array $security = []
    )
    {
    }

    public function getSecurity(): array
    {
        return $this->security;
    }

    public function addSecurity(ClarityProjectSecurity $security): self
    {
        $this->security[] = $security;

        return $this;
    }
}