<?php

declare(strict_types=1);

namespace App\Entity\Messenger;

use App\Entity\User\User;
use App\Enum\Messenger\Messenger;
use DateTimeInterface;

class MessengerUser
{
    public function __construct(
        private string $id,
        private readonly Messenger $messenger,
        private readonly string $identifier,
        private ?string $username = null,
        private ?string $name = null,
        private ?User $user = null,
        private bool $showExtendedKeyboard = false,
        private ?array $botIds = null,
        private ?array $usernameHistory = null,
        private ?DateTimeInterface $createdAt = null,
        private ?DateTimeInterface $updatedAt = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
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

    public function showExtendedKeyboard(): bool
    {
        return $this->showExtendedKeyboard;
    }

    public function setShowExtendedKeyboard(bool $showExtendedKeyboard): self
    {
        $this->showExtendedKeyboard = $showExtendedKeyboard;

        return $this;
    }

    /**
     * @return int[]|null
     */
    public function getBotIds(): ?array
    {
        return $this->botIds;
    }

    public function addBotId(int $botId): self
    {
        if ($this->botIds === null) {
            $this->botIds = [];
        }

        $this->botIds[] = $botId;
        $this->botIds = array_filter(array_unique($this->botIds));

        return $this;
    }

    public function removeBotId(int $botId): self
    {
        if ($this->botIds === null) {
            return $this;
        }

        $this->botIds = array_unique(array_diff($this->botIds, [$botId]));

        return $this;
    }

    public function addUsernameHistory(string $username): self
    {
        if ($this->usernameHistory === null) {
            $this->usernameHistory = [];
        }

        $this->usernameHistory[] = $username;
        $this->usernameHistory = array_filter(array_unique($this->usernameHistory));

        return $this;
    }

    public function getUsernameHistory(): ?array
    {
        return $this->usernameHistory;
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