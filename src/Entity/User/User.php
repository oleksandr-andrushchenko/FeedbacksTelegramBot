<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Enum\Feedback\Rating;
use DateTimeImmutable;
use DateTimeInterface;

class User
{
    public function __construct(
        private ?string $username = null,
        private ?string $name = null,
        private ?string $countryCode = null,
        private ?string $localeCode = null,
        private ?string $currencyCode = null,
        private ?string $timezone = null,
        private ?Rating $rating = null,
        private ?int $phoneNumber = null,
        private ?string $email = null,
        private ?DateTimeInterface $subscriptionExpireAt = null,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?DateTimeInterface $updatedAt = null,
        private ?DateTimeInterface $purgedAt = null,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(?string $localeCode): self
    {
        $this->localeCode = $localeCode;

        return $this;
    }


    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
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

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function setRating(?Rating $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getPhoneNumber(): ?int
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?int $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSubscriptionExpireAt(): ?DateTimeInterface
    {
        return $this->subscriptionExpireAt;
    }

    public function setSubscriptionExpireAt(?DateTimeInterface $subscriptionExpireAt): self
    {
        $this->subscriptionExpireAt = $subscriptionExpireAt;

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

    public function getPurgedAt(): ?DateTimeInterface
    {
        return $this->purgedAt;
    }

    public function setPurgedAt(?DateTimeInterface $purgedAt): self
    {
        $this->purgedAt = $purgedAt;

        return $this;
    }
}
