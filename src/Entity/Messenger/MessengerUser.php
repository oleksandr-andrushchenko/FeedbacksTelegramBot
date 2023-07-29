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
        private ?string $languageCode = null,
        private ?User $user = null,
        private bool $isShowHints = true,
        private ?DateTimeInterface $createdAt = null,
        private ?DateTimeInterface $updatedAt = null,
        private ?int $id = null,
    )
    {
        $this->id = null;
        $this->createdAt = $this->createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $this->updatedAt ?? null;
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

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(?string $languageCode): self
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    public function isShowHints(): bool
    {
        return $this->isShowHints;
    }

    public function setIsShowHints(bool $isShowHints): self
    {
        $this->isShowHints = $isShowHints;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
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