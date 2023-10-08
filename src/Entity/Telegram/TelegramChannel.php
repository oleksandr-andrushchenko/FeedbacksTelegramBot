<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Enum\Telegram\TelegramGroup;
use DateTimeImmutable;
use DateTimeInterface;

class TelegramChannel
{
    public function __construct(
        private readonly string $username,
        private TelegramGroup $group,
        private string $name,
        private string $countryCode,
        private string $localeCode,
        private ?string $region1 = null,
        private ?string $region2 = null,
        private ?string $locality = null,
        private bool $primary = true,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?DateTimeInterface $updatedAt = null,
        private ?DateTimeInterface $deletedAt = null,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(string $localeCode): self
    {
        $this->localeCode = $localeCode;

        return $this;
    }

    public function getRegion1(): ?string
    {
        return $this->region1;
    }

    public function setRegion1(string $region1 = null): self
    {
        $this->region1 = $region1;

        return $this;
    }

    public function getRegion2(): ?string
    {
        return $this->region2;
    }

    public function setRegion2(string $region2 = null): self
    {
        $this->region2 = $region2;

        return $this;
    }

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function setLocality(string $locality = null): self
    {
        $this->locality = $locality;

        return $this;
    }

    public function getGroup(): TelegramGroup
    {
        return $this->group;
    }

    public function setGroup(TelegramGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function primary(): bool
    {
        return $this->primary;
    }

    public function setPrimary(bool $primary): self
    {
        $this->primary = $primary;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }
}
