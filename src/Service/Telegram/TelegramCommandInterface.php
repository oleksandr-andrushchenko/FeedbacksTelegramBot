<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Closure;

interface TelegramCommandInterface
{
    public function getName(): string;

    public function getCallback(): Closure;

    public function getKeyboardOnly(): bool;

    public function getKey(): ?string;

    public function getBeforeConversations(): bool;
}
