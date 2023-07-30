<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Telegram\TelegramAwareHelper;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseActionTelegramChatSender
{
    public function __construct(
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
    )
    {
    }

    public function sendActions(TelegramAwareHelper $tg): null
    {
        $keyboards = [
            $this->getCreateButton($tg),
            $this->getSearchButton($tg),
        ];

        if ($this->userSubscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser())) {
            $keyboards[] = $this->getSubscriptionsButton($tg);
        } elseif ($tg->getTelegram()->getOptions()->acceptPayments()) {
            $keyboards[] = $this->getPremiumButton($tg);
        }

        return $tg->reply($this->getActionAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public static function getActionAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('ask.action.action');
    }

    public static function getCreateButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->transCommand('create'));
    }

    public static function getSearchButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->transCommand('search'));
    }

    public static function getPremiumButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->transCommand('premium'));
    }

    public static function getSubscriptionsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->transCommand('subscriptions'));
    }
}
