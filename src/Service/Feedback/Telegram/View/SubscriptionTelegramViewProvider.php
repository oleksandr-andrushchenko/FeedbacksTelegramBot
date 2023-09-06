<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Feedback\Subscription\FeedbackSubscriptionPlanProvider;
use App\Service\Intl\CurrencyProvider;
use App\Service\Intl\TimeProvider;
use App\Service\MoneyFormatter;
use App\Service\Telegram\TelegramAwareHelper;

class SubscriptionTelegramViewProvider
{
    public function __construct(
        private readonly TimeProvider $timeProvider,
        private readonly CurrencyProvider $currencyProvider,
        private readonly MoneyFormatter $moneyFormatter,
        private readonly FeedbackSubscriptionPlanProvider $subscriptionPlanProvider,
        private readonly FeedbackSubscriptionManager $manager,
    )
    {
    }

    public function getSubscriptionTelegramView(
        TelegramAwareHelper $tg,
        FeedbackUserSubscription $subscription,
        int $number = null
    ): string
    {
        $currencyCode = $subscription->getPayment()->getPrice()->getCurrency();
        $currency = $this->currencyProvider->getCurrency($currencyCode);
        $price = $subscription->getPayment()->getPrice();
        $subscriptionPlan = $subscription->getSubscriptionPlan();

        $parameters = [
            'number' => $number,
            'subscription_plan' => $this->subscriptionPlanProvider->getSubscriptionPlanName($subscriptionPlan),
            'currency' => $this->currencyProvider->getCurrencyComposeName($currency),
            'price' => $this->moneyFormatter->formatMoney($price, native: true),
            'is_subscription_active' => $this->manager->isSubscriptionActive($subscription),
            'period' => $this->timeProvider->getShortDateInterval(
                $subscription->getCreatedAt(),
                $subscription->getExpireAt(),
                timezone: $tg->getTimezone(),
                localeCode: $tg->getLocaleCode()
            ),
        ];

        return $tg->view('subscription', $parameters);
    }
}