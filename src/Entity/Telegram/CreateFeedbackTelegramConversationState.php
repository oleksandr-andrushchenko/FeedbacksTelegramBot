<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Enum\Feedback\Rating;
use App\Object\Feedback\SearchTermTransfer;

class CreateFeedbackTelegramConversationState extends TelegramConversationState
{
    public function __construct(
        ?int $step = null,
        private ?SearchTermTransfer $searchTerm = null,
        private ?Rating $rating = null,
        private ?string $description = null,
        private ?bool $change = null,
    )
    {
        parent::__construct($step);
    }

    public function getSearchTerm(): ?SearchTermTransfer
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(?SearchTermTransfer $searchTerm): static
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function setRating(?Rating $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isChange(): ?bool
    {
        return $this->change;
    }

    public function setChange(?bool $change): static
    {
        $this->change = $change;

        return $this;
    }
}
