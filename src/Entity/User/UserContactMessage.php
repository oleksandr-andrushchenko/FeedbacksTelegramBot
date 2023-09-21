<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use DateTimeImmutable;
use DateTimeInterface;

class UserContactMessage
{
    public function __construct(
        private readonly ?MessengerUser $messengerUser,
        private readonly User $user,
        private readonly string $text,
        private readonly ?TelegramBot $telegramBot,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}