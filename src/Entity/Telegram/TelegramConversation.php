<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeImmutable;
use DateTimeInterface;

class TelegramConversation
{
    public function __construct(
        private readonly string $hash,
        private readonly int $messengerUserId,
        private readonly int $chatId,
        private readonly int $botId,
        private readonly string $class,
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

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getMessengerUserId(): int
    {
        return $this->messengerUserId;
    }

    public function getChatId(): int
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
