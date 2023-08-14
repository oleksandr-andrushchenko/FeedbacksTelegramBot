<?php

declare(strict_types=1);

namespace App\Object\Messenger;

use App\Enum\Messenger\Messenger;

readonly class MessengerUserTransfer
{
    public function __construct(
        private Messenger $messenger,
        private string $id,
        private ?string $username,
        private ?string $name,
        private ?string $countryCode,
        private ?string $localeCode,
        private ?string $currencyCode,
    )
    {
    }

    public function getMessenger(): Messenger
    {
        return $this->messenger;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }
}