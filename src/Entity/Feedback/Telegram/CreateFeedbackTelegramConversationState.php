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
        private ?int $feedbackId = null,
    )
    {
        parent::__construct($step, $skipHelpButtons, $searchTerm, $change);
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

    public function getFeedbackId(): ?int
    {
        return $this->feedbackId;
    }

    public function setFeedbackId(?int $feedbackId): self
    {
        $this->feedbackId = $feedbackId;

        return $this;
    }
}
