<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Closure;

readonly class TelegramCommand implements TelegramCommandInterface
{
    public function __construct(
        private string $name,
        private Closure $callback,
        private bool $menu = false,
        private ?string $key = null,
        private bool $beforeConversations = false,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCallback(): Closure
    {
        return $this->callback;
    }

    public function isMenu(): bool
    {
        return $this->menu;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getBeforeConversations(): bool
    {
        return $this->beforeConversations;
    }
}