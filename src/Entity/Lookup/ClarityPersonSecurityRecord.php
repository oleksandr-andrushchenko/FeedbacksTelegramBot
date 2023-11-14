<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

class ClarityPersonSecurityRecord
{
    public function __construct(
        /**
         * @var ClarityPersonSecurity[]
         */
        private array $security = []
    )
    {
    }

    public function getSecurity(): array
    {
        return $this->security;
    }

    public function addSecurity(ClarityPersonSecurity $security): self
    {
        $this->security[] = $security;

        return $this;
    }
}