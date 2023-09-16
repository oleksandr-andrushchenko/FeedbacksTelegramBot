<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Service\Telegram\Command\TelegramCommand;
use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScope;
use Longman\TelegramBot\Entities\Entity;

class TelegramMyCommands
{
    public function __construct(
        private readonly array $commands,
        private readonly BotCommandScope $scope,
        private readonly string $localeCode,
    )
    {
    }

    /**
     * @return string[]|TelegramCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return BotCommandScope|Entity
     */
    public function getScope(): BotCommandScope
    {
        return $this->scope;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }
}