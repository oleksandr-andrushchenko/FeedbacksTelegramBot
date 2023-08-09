<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramPayment;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use DateTimeImmutable;
use DateTimeInterface;

class FeedbackUserSubscription
{
    public function __construct(
        private readonly MessengerUser $messengerUser,
        private readonly FeedbackSubscriptionPlanName $subscriptionPlan,
        private readonly DateTimeInterface $expireAt,
        private readonly ?TelegramPayment $payment = null,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?DateTimeInterface $updatedAt = null,
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessengerUser(): MessengerUser
    {
        return $this->messengerUser;
    }

    public function getSubscriptionPlan(): FeedbackSubscriptionPlanName
    {
        return $this->subscriptionPlan;
    }

    public function getPayment(): TelegramPayment
    {
        return $this->payment;
    }

    public function getExpireAt(): DateTimeInterface
    {
        return $this->expireAt;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
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
}
