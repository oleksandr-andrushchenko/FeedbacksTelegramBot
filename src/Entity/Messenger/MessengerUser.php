<?php

declare(strict_types=1);

namespace App\Entity\Messenger;

use App\Entity\User\User;
use App\Enum\Messenger\Messenger;
use DateTimeImmutable;
use DateTimeInterface;

class MessengerUser
{
    public function __construct(
        private readonly Messenger $messenger,
        private readonly string $identifier,
        private ?string $username = null,
        private ?string $name = null,
        private ?string $countryCode = null,
        private ?string $localeCode = null,
        private ?User $user = null,
        private bool $isShowHints = false,
        private bool $isShowExtendedKeyboard = false,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?DateTimeInterface $updatedAt = null,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getMessenger(): Messenger
    {
        return $this->messenger;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

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

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(?string $localeCode): self
    {
        $this->localeCode = $localeCode;

        return $this;
    }

    public function showHints(): bool
    {
        return $this->isShowHints;
    }

    public function setIsShowHints(bool $isShowHints): self
    {
        $this->isShowHints = $isShowHints;

        return $this;
    }

    public function showExtendedKeyboard(): bool
    {
        return $this->isShowExtendedKeyboard;
    }

    public function setIsShowExtendedKeyboard(bool $isShowExtendedKeyboard): self
    {
        $this->isShowExtendedKeyboard = $isShowExtendedKeyboard;

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
}