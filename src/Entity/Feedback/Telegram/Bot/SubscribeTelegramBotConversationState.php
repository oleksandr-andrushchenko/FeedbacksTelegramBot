<?php

declare(strict_types=1);

namespace App\Entity\Feedback\Telegram\Bot;

use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Entity\Intl\Currency;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Entity\Telegram\TelegramBotPaymentMethod;

class SubscribeTelegramBotConversationState extends TelegramBotConversationState
{
    public function __construct(
        ?int $step = null,
        ?array $skipHelpButtons = null,
        private ?bool $currencyStep = null,
        private ?bool $paymentMethodStep = null,
        private ?FeedbackSubscriptionPlan $subscriptionPlan = null,
        private ?TelegramBotPaymentMethod $paymentMethod = null,
        private ?Currency $currency = null,
    )
    {
        parent::__construct($step, $skipHelpButtons);
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

    public function getPaymentMethod(): ?TelegramBotPaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?TelegramBotPaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function isPaymentMethodStep(): ?bool
    {
        return $this->paymentMethodStep;
    }

    public function setPaymentMethodStep(?bool $paymentMethodStep): static
    {
        $this->paymentMethodStep = $paymentMethodStep;

        return $this;
    }

    public function isCurrencyStep(): ?bool
    {
        return $this->currencyStep;
    }

    public function setCurrencyStep(?bool $currencyStep): static
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
