<?php

declare(strict_types=1);

namespace App\Entity\Feedback\Telegram;

use App\Enum\Feedback\Rating;
use App\Object\Feedback\SearchTermTransfer;

class CreateFeedbackTelegramConversationState extends SearchTermAwareTelegramConversationState
{
    public function __construct(
        ?int $step = null,
        ?array $skipHelpButtons = null,
        ?SearchTermTransfer $searchTerm = null,
        ?bool $change = null,
        private ?Rating $rating = null,
        private ?string $description = null,
    )
    {
        parent::__construct($step, $skipHelpButtons, $searchTerm, $change);
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
}
