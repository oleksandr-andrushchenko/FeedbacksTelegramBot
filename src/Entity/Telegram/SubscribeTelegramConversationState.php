<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Entity\Intl\Currency;

class SubscribeTelegramConversationState extends TelegramConversationState
{
    public function __construct(
        ?int $step = null,
        private ?bool $currencyStep = null,
        private ?bool $paymentMethodStep = null,
        private ?FeedbackSubscriptionPlan $subscriptionPlan = null,
        private ?TelegramPaymentMethod $paymentMethod = null,
        private ?Currency $currency = null,
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

    public function isCurrencyStep(): ?bool
    {
        return $this->currencyStep;
    }

    public function setIsCurrencyStep(?bool $currencyStep): static
    {
        $this->currencyStep = $currencyStep;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }
}
