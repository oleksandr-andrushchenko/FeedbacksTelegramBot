<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Enum\Telegram\TelegramView;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Telegram\TelegramAwareHelper;

class FeedbackSubscriptionsTelegramChatSender
{
    public function __construct(
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
    )
    {
    }

    public function sendFeedbackSubscriptions(TelegramAwareHelper $tg): null
    {
        $userSubscriptions = $this->userSubscriptionManager->getSubscriptions($tg->getTelegram()->getMessengerUser());

        $count = count($userSubscriptions);

        if ($count === 0) {
            return $tg->replyUpset('reply.subscriptions.empty_list')->null();
        }

        $tg->reply($tg->trans('reply.subscriptions.title') . ':');

        foreach ($userSubscriptions as $index => $userSubscription) {
            $tg->replyView(TelegramView::SUBSCRIPTION, [
                'number' => $index + 1,
                'subscription' => $userSubscription,
                'is_subscription_active' => $this->userSubscriptionManager->isSubscriptionActive($userSubscription),
                'datetime_format' => $tg->trans('datetime_format'),
                'date_format' => $tg->trans('date_format'),
            ]);
        }

        return $tg->replyOk('reply.subscriptions.summary', ['count' => $count])->null();
    }
}
