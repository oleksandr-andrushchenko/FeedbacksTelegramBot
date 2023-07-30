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
        $userSubscriptions = $this->userSubscriptionManager->getSubscriptions($tg->getTelegram()->getMessengerUser());

        $count = count($userSubscriptions);

        if ($count === 0) {
            return $tg->replyUpset('reply.subscriptions.empty_list')->null();
        }

        $tg->reply($tg->trans('reply.subscriptions.title'));

        foreach (array_reverse($userSubscriptions, true) as $index => $userSubscription) {
            $tg->replyView(TelegramView::SUBSCRIPTION, [
                'number' => $index + 1,
                'subscription' => $userSubscription,
            ]);
        }

        return $tg->replyOk('reply.subscriptions.summary', ['count' => $count])->null();
    }
}
