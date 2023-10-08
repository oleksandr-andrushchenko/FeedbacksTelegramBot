<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeImmutable;
use DateTimeInterface;

class TelegramBotUpdate
{
    public function __construct(
        private readonly int $id,
        private readonly array $data,
        private readonly TelegramBot $bot,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
    )
    {
    }

    public function getId(): int
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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
