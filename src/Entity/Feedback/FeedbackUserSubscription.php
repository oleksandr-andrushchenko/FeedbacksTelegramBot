<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\User\User;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use DateTimeInterface;
use Stringable;

class FeedbackUserSubscription implements Stringable
{
    public function __construct(
        private readonly string $id,
        private readonly User $user,
        private readonly FeedbackSubscriptionPlanName $subscriptionPlan,
        private readonly DateTimeInterface $expireAt,
        private readonly ?MessengerUser $messengerUser = null,
        private readonly ?TelegramBotPayment $telegramPayment = null,
        private ?DateTimeInterface $createdAt = null,
        private ?DateTimeInterface $updatedAt = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getSubscriptionPlan(): FeedbackSubscriptionPlanName
    {
        return $this->subscriptionPlan;
    }

    public function getTelegramPayment(): ?TelegramBotPayment
    {
        return $this->telegramPayment;
    }

    public function getExpireAt(): DateTimeInterface
    {
        return $this->expireAt;
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

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}
