<?php

declare(strict_types=1);

namespace App\Entity\Address;

readonly class AddressComponent
{
    public function __construct(
        private string $shortName,
    )
    {
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }
}
