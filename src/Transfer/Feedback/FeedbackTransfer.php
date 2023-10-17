<?php

declare(strict_types=1);

namespace App\Transfer\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\Rating;

class FeedbackTransfer
{
    public function __construct(
        private ?MessengerUser $messengerUser = null,
        private ?array $searchTerms = null,
        private ?Rating $rating = null,
        private ?string $description = null,
        private ?TelegramBot $telegramBot = null,
    )
    {
        $this->setSearchTerms($this->searchTerms);
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function setMessengerUser(?MessengerUser $messengerUser): self
    {
        $this->messengerUser = $messengerUser;

        return $this;
    }

    /**
     * @return SearchTermTransfer[]
     */
    public function getSearchTerms(): array
    {
        return $this->searchTerms;
    }

    public function setSearchTerms(?array $searchTerms): self
    {
        if (is_array($searchTerms)) {
            array_map(static fn ($searchTerm): bool => assert($searchTerm instanceof SearchTermTransfer), $this->searchTerms);
        }

        $this->searchTerms = $searchTerms;

        return $this;
    }

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function setRating(?Rating $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }

    public function setTelegramBot(?TelegramBot $telegramBot): self
    {
        $this->telegramBot = $telegramBot;

        return $this;
    }
}