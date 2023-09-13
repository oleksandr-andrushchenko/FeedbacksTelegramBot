<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Entity\Messenger\MessengerUser;
use DateTimeImmutable;
use DateTimeInterface;

class TelegramConversation
{
    public function __construct(
        private readonly MessengerUser $messengerUser,
        private readonly int $chatId,
        private string $class,
        private readonly TelegramBot $bot,
        private bool $active = true,
        private ?array $state = null,
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

    public function getMessengerUser(): MessengerUser
    {
        return $this->messengerUser;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function setClass(string $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
    }

    public function getState(): ?array
    {
        return $this->state;
    }

    public function setState(?array $state): self
    {
        $this->state = $state;

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
