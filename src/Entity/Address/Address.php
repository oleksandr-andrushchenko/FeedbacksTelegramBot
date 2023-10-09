<?php

declare(strict_types=1);

namespace App\Entity\Address;

use DateTimeImmutable;
use DateTimeInterface;

class Address
{
    public function __construct(
        private readonly string $countryCode,
        private readonly string $administrativeAreaLevel1,
        private readonly ?string $administrativeAreaLevel2 = null,
        private readonly ?string $administrativeAreaLevel3 = null,
        private ?string $timezone = null,
        private int $count = 0,
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

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getAdministrativeAreaLevel1(): ?string
    {
        return $this->administrativeAreaLevel1;
    }

    public function getAdministrativeAreaLevel2(): ?string
    {
        return $this->administrativeAreaLevel2;
    }

    public function getAdministrativeAreaLevel3(): ?string
    {
        return $this->administrativeAreaLevel3;
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
