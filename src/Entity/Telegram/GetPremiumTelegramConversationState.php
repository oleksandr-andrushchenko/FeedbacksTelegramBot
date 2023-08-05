<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Entity\Feedback\FeedbackSubscriptionPlan;

class GetPremiumTelegramConversationState extends TelegramConversationState
{
    public function __construct(
        ?int $step = null,
        private ?bool $paymentMethodStep = null,
        private ?FeedbackSubscriptionPlan $subscriptionPlan = null,
        private ?TelegramPaymentMethod $paymentMethod = null,
    )
    {
        parent::__construct($step);
    }

    public function getSubscriptionPlan(): ?FeedbackSubscriptionPlan
    {
        return $this->subscriptionPlan;
    }

    public function setSubscriptionPlan(?FeedbackSubscriptionPlan $subscriptionPlan): static
    {
        $this->subscriptionPlan = $subscriptionPlan;

        return $this;
    }

    public function getPaymentMethod(): ?TelegramPaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?TelegramPaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function isPaymentMethodStep(): ?bool
    {
        return $this->paymentMethodStep;
    }

    public function setIsPaymentMethodStep(?bool $paymentMethodStep): static
    {
        $this->paymentMethodStep = $paymentMethodStep;

        return $this;
    }
}
