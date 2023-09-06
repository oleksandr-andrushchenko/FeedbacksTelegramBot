<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Chat;

use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Feedback\Telegram\View\SubscriptionTelegramViewProvider;
use App\Service\Telegram\TelegramAwareHelper;

class SubscriptionsTelegramChatSender
{
    public function __construct(
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly SubscriptionTelegramViewProvider $subscriptionViewProvider
    )
    {
    }

    public function sendFeedbackSubscriptions(TelegramAwareHelper $tg): null
    {
        $messangerUser = $tg->getTelegram()->getMessengerUser();
        $subscriptions = $this->subscriptionManager->getSubscriptions($messangerUser);

        $count = count($subscriptions);

        if ($count === 0) {
            $message = $tg->trans('reply.empty_list', domain: 'subscriptions');
            $message = $tg->upsetText($message);

            return $tg->reply($message)->null();
        }

        $parameters = [
            'count' => $count,
        ];
        $message = $tg->trans('reply.title', $parameters, domain: 'subscriptions');

        $tg->reply($message);

        foreach (array_reverse($subscriptions, true) as $index => $subscription) {
            $message = $this->subscriptionViewProvider->getSubscriptionTelegramView($tg, $subscription, $index + 1);

            $tg->reply($message);
        }

        return null;
    }
}
