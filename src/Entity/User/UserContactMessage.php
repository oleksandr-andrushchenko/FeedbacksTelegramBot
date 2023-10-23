<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use DateTimeImmutable;
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
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}