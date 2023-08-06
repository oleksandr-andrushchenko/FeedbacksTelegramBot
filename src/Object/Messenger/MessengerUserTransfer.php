<?php

declare(strict_types=1);

namespace App\Object\Messenger;

use App\Enum\Messenger\Messenger;

class MessengerUserTransfer
{
    public function __construct(
        private readonly Messenger $messenger,
        private readonly string $id,
        private readonly ?string $username,
        private readonly ?string $name,
        private readonly ?string $localeCode,
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

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }
}