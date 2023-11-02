<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeInterface;

class TelegramBotUpdate
{
    public function __construct(
        private readonly string $id,
        private readonly array $data,
        private readonly TelegramBot $bot,
        private ?DateTimeInterface $createdAt = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
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
}
