<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

readonly class TelegramInvoicePhoto
{
    public function __construct(
        private string $url,
        private ?int $size = null,
        private ?int $width = null,
        private ?int $height = null
    )
    {
    }

    public function url(): string
    {
        return $this->url;
    }

    public function size(): ?int
    {
        return $this->size;
    }

    public function width(): ?int
    {
        return $this->width;
    }

    public function height(): ?int
    {
        return $this->height;
    }
}