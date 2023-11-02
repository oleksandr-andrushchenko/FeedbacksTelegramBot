<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use DateTimeInterface;
use Stringable;

class UserContactMessage implements Stringable
{
    public function __construct(
        private readonly string $id,
        private readonly ?MessengerUser $messengerUser,
        private readonly User $user,
        private readonly string $text,
        private readonly ?TelegramBot $telegramBot,
        private ?DateTimeInterface $createdAt = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}