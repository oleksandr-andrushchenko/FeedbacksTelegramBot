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

        if ($tg->getTelegram()->getMessengerUser()->isShowExtendedKeyboard()) {
            $keyboards[] = $this->getCountryButton($tg);
            $keyboards[] = $this->getHintsButton($tg);
            $keyboards[] = $this->getPurgeButton($tg);
            $keyboards[] = $this->getRestartButton($tg);
            $keyboards[] = $this->getShowLessButton($tg);
        } else {
            $keyboards[] = $this->getShowMoreButton($tg);
        }

        return $tg->reply($this->getActionAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public static function getActionAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('ask.action.action');
    }

    public static function getCreateButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'create'));
    }

    public static function getSearchButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'search'));
    }

    public static function getPremiumButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'premium'));
    }

    public static function getSubscriptionsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'subscriptions'));
    }

    public static function getCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'country'));
    }

    public static function getHintsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'hints'));
    }

    public static function getPurgeButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'purge'));
    }

    public static function getRestartButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'restart'));
    }

    public static function getShowMoreButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.more'));
    }

    public static function getShowLessButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.less'));
    }

    private static function command($tg, string $key): string
    {
        $domain = sprintf('tg.%s', $tg->getTelegram()->getName()->name);

        return trim(join(' ', [
            $tg->trans(sprintf('icon.%s', $key), domain: $domain),
            $tg->trans(sprintf('command.%s', $key), domain: $domain),
        ]));
    }
}
