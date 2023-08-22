<?php

declare(strict_types=1);

namespace App\Service\Feedback\View;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Service\Intl\CurrencyProvider;
use App\Service\Intl\TimeProvider;
use App\Service\MoneyFormatter;
use App\Service\Telegram\TelegramAwareHelper;
use Twig\Environment;

class SubscriptionTelegramViewProvider
{
    public function __construct(
        private readonly Environment $twig,
        private readonly TimeProvider $timeProvider,
        private readonly CurrencyProvider $currencyProvider,
        private readonly MoneyFormatter $moneyFormatter,
    )
    {
    }

    public function getSubscriptionTelegramView(TelegramAwareHelper $tg, FeedbackUserSubscription $subscription, int $number = null): string
    {
        $currency = $this->currencyProvider->getCurrency($subscription->getPayment()->getPrice()->getCurrency());

        return $this->twig->render('tg.subscription.html.twig', [
            'number' => $number,
            'subscription' => $subscription,
            'currency' => $this->currencyProvider->getComposeCurrencyName($currency),
            'price' => $this->moneyFormatter->formatMoney($subscription->getPayment()->getPrice()),
            'created_at' => $this->timeProvider->getShortDate(
                $subscription->getCreatedAt(),
                timezone: $tg->getTimezone(),
                localeCode: $tg->getLocaleCode()
            ),
            'expire_at' => $this->timeProvider->getShortDate(
                $subscription->getCreatedAt(),
                timezone: $tg->getTimezone(),
                localeCode: $tg->getLocaleCode()
            ),
        ]);
    }
}