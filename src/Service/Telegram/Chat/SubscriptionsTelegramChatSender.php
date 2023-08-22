<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Service\Feedback\FeedbackSubscriptionManager;
use App\Service\Feedback\View\SubscriptionTelegramViewProvider;
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
        $subscriptions = $this->subscriptionManager->getSubscriptions($tg->getTelegram()->getMessengerUser());

        $count = count($subscriptions);

        if ($count === 0) {
            return $tg->replyUpset($tg->trans('reply.empty_list', domain: 'tg.subscriptions'))->null();
        }

        $tg->reply($tg->trans('reply.title', ['count' => $count], domain: 'tg.subscriptions'));

        foreach (array_reverse($subscriptions, true) as $index => $subscription) {
            $tg->reply($this->subscriptionViewProvider->getSubscriptionTelegramView($tg, $subscription, $index + 1));
        }

        return null;
    }
}
