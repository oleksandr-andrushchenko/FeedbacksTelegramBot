<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Enum\Telegram\TelegramView;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Telegram\TelegramAwareHelper;

class SubscriptionsTelegramChatSender
{
    public function __construct(
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
    )
    {
    }

    public function sendFeedbackSubscriptions(TelegramAwareHelper $tg): null
    {
        $subscriptions = $this->userSubscriptionManager->getSubscriptions($tg->getTelegram()->getMessengerUser());

        $count = count($subscriptions);

        if ($count === 0) {
            return $tg->replyUpset($tg->trans('reply.subscriptions.empty_list'))->null();
        }

        $tg->reply($tg->trans('reply.subscriptions.title', ['count' => $count]));

        foreach (array_reverse($subscriptions, true) as $index => $userSubscription) {
            $tg->replyView(TelegramView::SUBSCRIPTION, [
                'number' => $index + 1,
                'subscription' => $userSubscription,
            ]);
        }

        return null;
    }
}
