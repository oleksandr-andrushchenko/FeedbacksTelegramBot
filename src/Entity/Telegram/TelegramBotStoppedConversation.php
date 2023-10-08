<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeImmutable;
use DateTimeInterface;

class TelegramBotStoppedConversation
{
    public function __construct(
        private readonly int $messengerUserId,
        private readonly int $chatId,
        private readonly int $botId,
        private readonly string $class,
        private readonly array $state,
        private readonly DateTimeInterface $startedAt,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStartedAt(): DateTimeInterface
    {
        return $this->startedAt;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
