<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Closure;

class FallbackTelegramCommand implements TelegramCommandInterface
{
    public function __construct(
        private readonly Closure $callback,
        private readonly bool $keyboardOnly = false,
        private readonly bool $beforeConversations = false,
    )
    {
    }

    public function getName(): string
    {
        return '';
    }

    public function getCallback(): Closure
    {
        return $this->callback;
    }

    public function getKeyboardOnly(): bool
    {
        return $this->keyboardOnly;
    }

    public function getKey(): ?string
    {
        return null;
    }

    public function getBeforeConversations(): bool
    {
        return $this->beforeConversations;
    }
}