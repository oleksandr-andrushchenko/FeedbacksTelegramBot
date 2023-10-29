<?php

declare(strict_types=1);

namespace App\Entity\Intl;

class Level1Region
{
    public function __construct(
        private readonly string $id,
        private readonly string $countryCode,
        private readonly string $name,
        private ?string $timezone = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getName(): string
    {
        return $this->name;
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
}
