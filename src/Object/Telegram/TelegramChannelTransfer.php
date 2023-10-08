<?php

declare(strict_types=1);

namespace App\Object\Telegram;

use App\Entity\Intl\Country;
use App\Entity\Intl\Locale;
use App\Enum\Telegram\TelegramGroup;

class TelegramChannelTransfer
{
    public function __construct(
        private readonly string $username,
        private ?TelegramGroup $group = null,
        private bool $groupPassed = false,
        private ?string $name = null,
        private bool $namePassed = false,
        private ?Country $country = null,
        private bool $countryPassed = false,
        private ?Locale $locale = null,
        private bool $localePassed = false,
        private ?string $region1 = null,
        private bool $region1Passed = false,
        private ?string $region2 = null,
        private bool $region2Passed = false,
        private ?string $locality = null,
        private bool $localityPassed = false,
        private ?bool $primary = null,
        private bool $primaryPassed = false,
    )
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setGroup(TelegramGroup $group): self
    {
        $this->group = $group;
        $this->groupPassed = true;

        return $this;
    }

    public function groupPassed(): bool
    {
        return $this->groupPassed;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->namePassed = true;

        return $this;
    }

    public function namePassed(): bool
    {
        return $this->namePassed;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(Country $country): self
    {
        $this->country = $country;
        $this->countryPassed = true;

        return $this;
    }

    public function countryPassed(): bool
    {
        return $this->countryPassed;
    }


    public function getLocale(): ?Locale
    {
        return $this->locale;
    }

    public function setLocale(?Locale $locale): self
    {
        $this->locale = $locale;
        $this->localePassed = true;

        return $this;
    }

    public function localePassed(): bool
    {
        return $this->localePassed;
    }

    public function getRegion1(): ?string
    {
        return $this->region1;
    }

    public function setRegion1(?string $region1): self
    {
        $this->region1 = $region1;
        $this->region1Passed = true;

        return $this;
    }

    public function region1Passed(): bool
    {
        return $this->region1Passed;
    }

    public function getRegion2(): ?string
    {
        return $this->region2;
    }

    public function setRegion2(?string $region2): self
    {
        $this->region2 = $region2;
        $this->region2Passed = true;

        return $this;
    }

    public function region2Passed(): bool
    {
        return $this->region2Passed;
    }

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function setLocality(?string $locality): self
    {
        $this->locality = $locality;
        $this->localityPassed = true;

        return $this;
    }

    public function localityPassed(): bool
    {
        return $this->localityPassed;
    }

    public function getGroup(): ?TelegramGroup
    {
        return $this->group;
    }

    public function primary(): ?bool
    {
        return $this->primary;
    }

    public function setPrimary(bool $primary): self
    {
        $this->primary = $primary;
        $this->primaryPassed = true;

        return $this;
    }

    public function primaryPassed(): bool
    {
        return $this->primaryPassed;
    }
}