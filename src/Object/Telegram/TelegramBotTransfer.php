<?php

declare(strict_types=1);

namespace App\Object\Telegram;

use App\Enum\Telegram\TelegramGroup;

readonly class TelegramBotTransfer
{

    public function __construct(
        private string $username,
        private string $token,
        private string $countryCode,
        private string $localeCode,
        private TelegramGroup $group,
        private ?string $primaryBotUsername = null,
    )
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }

    public function getGroup(): TelegramGroup
    {
        return $this->group;
    }

    public function getPrimaryBotUsername(): ?string
    {
        return $this->primaryBotUsername;
    }
}