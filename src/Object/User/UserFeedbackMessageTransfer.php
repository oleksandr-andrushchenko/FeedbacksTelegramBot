<?php

declare(strict_types=1);

namespace App\Object\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;

readonly class UserFeedbackMessageTransfer
{
    public function __construct(
        private ?MessengerUser $messengerUser,
        private User $user,
        private string $text,
        private ?TelegramBot $telegramBot,
    )
    {
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getUser(): ?User
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
}