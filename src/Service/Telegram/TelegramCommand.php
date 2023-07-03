<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Closure;

class TelegramCommand extends FallbackTelegramCommand implements TelegramCommandInterface
{
    public function __construct(
        private readonly string $name,
        readonly Closure $callback,
        readonly bool $keyboardOnly = true,
        private readonly ?string $key = null,
        readonly bool $beforeConversations = false,
    )
    {
        parent::__construct($callback, $keyboardOnly, $beforeConversations);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}