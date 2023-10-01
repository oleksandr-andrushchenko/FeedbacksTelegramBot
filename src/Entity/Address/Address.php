<?php

declare(strict_types=1);

namespace App\Entity\Address;

readonly class Address
{
    public function __construct(
        private string $countryCode,
        private AddressComponent $region1,
        private AddressComponent $region2,
        private AddressComponent $locality,
    )
    {
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getRegion1(): AddressComponent
    {
        return $this->region1;
    }

    public function getRegion2(): AddressComponent
    {
        return $this->region2;
    }

    public function getLocality(): AddressComponent
    {
        return $this->locality;
    }
}
