<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use DateTimeInterface;
use Stringable;

class FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification implements Stringable
{
    public function __construct(
        private readonly string $id,
        private readonly MessengerUser $messengerUser,
        private readonly FeedbackSearchTerm $feedbackSearchTerm,
        private readonly FeedbackSearch $feedbackSearch,
        private readonly FeedbackSearch $targetFeedbackSearch,
        private readonly TelegramBot $telegramBot,
        private ?DateTimeInterface $createdAt = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMessengerUser(): MessengerUser
    {
        return $this->messengerUser;
    }

    public function getFeedbackSearchTerm(): FeedbackSearchTerm
    {
        return $this->feedbackSearchTerm;
    }

    public function getFeedbackSearch(): FeedbackSearch
    {
        return $this->feedbackSearch;
    }

    public function getTargetFeedbackSearch(): FeedbackSearch
    {
        return $this->targetFeedbackSearch;
    }

    public function getTelegramBot(): TelegramBot
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
