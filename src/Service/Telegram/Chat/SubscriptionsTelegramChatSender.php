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
            return $tg->replyUpset($tg->trans('reply.empty_list', domain: 'tg.subscriptions'))->null();
        }

        $tg->reply($tg->trans('reply.title', ['count' => $count], domain: 'tg.subscriptions'));

        foreach (array_reverse($subscriptions, true) as $index => $userSubscription) {
            $tg->reply($tg->view(TelegramView::SUBSCRIPTION, [
                'number' => $index + 1,
                'subscription' => $userSubscription,
            ]), parseMode: 'HTML');
        }

        return null;
    }
}
