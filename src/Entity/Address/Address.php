<?php

declare(strict_types=1);

namespace App\Entity\Address;

class Address
{
    public function __construct(
        private readonly string $countryCode,
        private readonly string $region1,
        private readonly string $region2,
        private readonly string $locality,
        private ?string $timezone = null,
        private int $count = 0,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getRegion1(): string
    {
        return $this->region1;
    }

    public function getRegion2(): string
    {
        return $this->region2;
    }

    public function getLocality(): string
    {
        return $this->locality;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function incCount(): self
    {
        $this->count++;

        return $this;
    }
}
