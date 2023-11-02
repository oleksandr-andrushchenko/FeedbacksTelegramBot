<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeInterface;

class TelegramBotRequest
{
    public function __construct(
        private readonly string $method,
        private readonly null|int|string $chatId,
        private readonly ?int $inlineMessageId,
        private readonly array $data,
        private readonly TelegramBot $bot,
        private ?array $response = null,
        private ?DateTimeInterface $createdAt = null,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getChatId(): null|int|string
    {
        return $this->chatId;
    }

    public function getInlineMessageId(): int
    {
        return $this->inlineMessageId;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function setResponse(array $response = null): self
    {
        $this->response = $response;

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
}
