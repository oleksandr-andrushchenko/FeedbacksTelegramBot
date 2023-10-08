<?php

declare(strict_types=1);

namespace App\Transfer\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\Rating;

readonly class FeedbackTransfer
{
    public function __construct(
        private ?MessengerUser $messengerUser,
        private ?SearchTermTransfer $searchTerm,
        private ?Rating $rating,
        private ?string $description,
        private ?TelegramBot $telegramBot = null,
    )
    {
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getSearchTerm(): ?SearchTermTransfer
    {
        return $this->searchTerm;
    }

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }
}