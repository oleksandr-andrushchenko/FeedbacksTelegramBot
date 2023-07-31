<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeImmutable;
use DateTimeInterface;

class TelegramRequest
{
    public function __construct(
        private readonly string $method,
        private readonly ?int $chatId,
        private readonly ?int $inlineMessageId,
        private readonly array $data,
        private ?array $response = null,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
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

    public function getChatId(): int
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
}
