<?php

declare(strict_types=1);

namespace App\Transfer\Telegram;

use App\Entity\Intl\Country;
use App\Entity\Intl\Locale;
use App\Enum\Telegram\TelegramBotGroupName;

class TelegramChannelTransfer
{
    public function __construct(
        private readonly string $username,
        private ?TelegramBotGroupName $group = null,
        private bool $groupPassed = false,
        private ?string $name = null,
        private bool $namePassed = false,
        private ?Country $country = null,
        private bool $countryPassed = false,
        private ?Locale $locale = null,
        private bool $localePassed = false,
        private ?string $administrativeAreaLevel1 = null,
        private bool $administrativeAreaLevel1Passed = false,
        private ?string $administrativeAreaLevel2 = null,
        private bool $administrativeAreaLevel2Passed = false,
        private ?string $administrativeAreaLevel3 = null,
        private bool $administrativeAreaLevel3Passed = false,
        private ?bool $primary = null,
        private bool $primaryPassed = false,
    )
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setGroup(TelegramBotGroupName $group): self
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

    public function getAdministrativeAreaLevel1(): ?string
    {
        return $this->administrativeAreaLevel1;
    }

    public function setAdministrativeAreaLevel1(?string $administrativeAreaLevel1): self
    {
        $this->administrativeAreaLevel1 = $administrativeAreaLevel1;
        $this->administrativeAreaLevel1Passed = true;

        return $this;
    }

    public function administrativeAreaLevel1Passed(): bool
    {
        return $this->administrativeAreaLevel1Passed;
    }

    public function getAdministrativeAreaLevel2(): ?string
    {
        return $this->administrativeAreaLevel2;
    }

    public function setAdministrativeAreaLevel2(?string $administrativeAreaLevel2): self
    {
        $this->administrativeAreaLevel2 = $administrativeAreaLevel2;
        $this->administrativeAreaLevel2Passed = true;

        return $this;
    }

    public function administrativeAreaLevel2Passed(): bool
    {
        return $this->administrativeAreaLevel2Passed;
    }

    public function getAdministrativeAreaLevel3(): ?string
    {
        return $this->administrativeAreaLevel3;
    }

    public function setAdministrativeAreaLevel3(?string $administrativeAreaLevel3): self
    {
        $this->administrativeAreaLevel3 = $administrativeAreaLevel3;
        $this->administrativeAreaLevel3Passed = true;

        return $this;
    }

    public function administrativeAreaLevel3Passed(): bool
    {
        return $this->administrativeAreaLevel3Passed;
    }

    public function getGroup(): ?TelegramBotGroupName
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