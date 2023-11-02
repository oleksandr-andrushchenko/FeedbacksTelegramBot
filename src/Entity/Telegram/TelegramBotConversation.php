<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeInterface;

class TelegramBotConversation
{
    public function __construct(
        private readonly string $hash,
        private readonly string $messengerUserId,
        private readonly string $chatId,
        private readonly int $botId,
        private readonly string $class,
        private ?array $state = null,
        private ?DateTimeInterface $createdAt = null,
        private ?DateTimeInterface $updatedAt = null,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getMessengerUserId(): string
    {
        return $this->messengerUserId;
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function getBotId(): int
    {
        return $this->botId;
    }

    public function getClass(): string
    {
        return $this->class;
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
