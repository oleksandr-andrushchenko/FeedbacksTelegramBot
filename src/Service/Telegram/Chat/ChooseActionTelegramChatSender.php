<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\TelegramAwareHelper;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseActionTelegramChatSender
{
    public function __construct(
        private readonly FeedbackUserSubscriptionManager $subscriptionManager,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
    )
    {
    }

    public function sendActions(TelegramAwareHelper $tg): null
    {
        $keyboards = [];
        $keyboards[] = $this->getCreateButton($tg);
        $keyboards[] = $this->getSearchButton($tg);

        $messengerUser = $tg->getTelegram()->getMessengerUser();
        $hasActivePremium = $this->subscriptionManager->hasActiveSubscription($messengerUser);
        $showPremium = false;

        if ($tg->getTelegram()->getOptions()->acceptPayments() && !$hasActivePremium) {
            $keyboards[] = $this->getPremiumButton($tg);
            $showPremium = true;
        }

        if (!$showPremium && ($hasActivePremium || $this->subscriptionManager->hasSubscription($messengerUser))) {
            $keyboards[] = $this->getSubscriptionsButton($tg);
        }

        if ($tg->getTelegram()->getMessengerUser()?->isShowExtendedKeyboard()) {
            $keyboards[] = $this->getMessageButton($tg);
            $keyboards[] = $this->getCountryButton($tg);
            if ($tg->getCountryCode() !== null) {
                $keyboards[] = $this->getLocaleButton($tg);
            }
            $keyboards[] = $this->getHintsButton($tg);
            $keyboards[] = $this->getPurgeButton($tg);
            $keyboards[] = $this->getRestartButton($tg);
            $keyboards[] = $this->getShowLessButton($tg);
        } else {
            $keyboards[] = $this->getShowMoreButton($tg);
        }

        $keyboards = array_chunk($keyboards, 2);

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

    public function getSubscriptionsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $hasActivePremium = $this->subscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser());
        $key = 'subscriptions';
        $domain = sprintf('tg.%s', $tg->getTelegram()->getGroup()->name);

        return $tg->button(
            join(' ', [
                $tg->trans(sprintf('icon.%s', $hasActivePremium ? $key : 'premium'), domain: $domain),
                $tg->trans(sprintf('command.%s', $key), domain: $domain),
            ])
        );
    }

    public function getCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $countryCode = $tg->getTelegram()?->getMessengerUser()->getUser()->getCountryCode();
        $country = $countryCode === null ? null : $this->countryProvider->getCountry($countryCode);

        if ($country === null) {
            return $tg->button(self::command($tg, 'country'));
        }

        $domain = sprintf('tg.%s', $tg->getTelegram()->getGroup()->name);

        return $tg->button(
            join(' ', [
                $this->countryProvider->getCountryIcon($country),
                $tg->trans('command.country', domain: $domain),
            ])
        );
    }

    public function getLocaleButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $localeCode = $tg->getLocaleCode();
        $locale = $localeCode === null ? null : $this->localeProvider->getLocale($localeCode);

        if ($locale === null) {
            return $tg->button(self::command($tg, 'locale'));
        }

        $domain = sprintf('tg.%s', $tg->getTelegram()->getGroup()->name);

        return $tg->button(
            join(' ', [
                $this->localeProvider->getLocaleIcon($locale),
                $tg->trans('command.locale', domain: $domain),
            ])
        );
    }

    public static function getHintsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $isShowHints = $tg->getTelegram()->getMessengerUser()->isShowHints();
        $domain = sprintf('tg.%s', $tg->getTelegram()->getGroup()->name);

        return $tg->button(
            join(' ', [
                $tg->trans('icon.hints', domain: $domain),
                $tg->trans(sprintf('keyboard.hints.turn_%s', $isShowHints ? 'off' : 'on')),
            ])
        );
    }

    public static function getPurgeButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'purge'));
    }

    public static function getMessageButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'message'));
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
        $domain = sprintf('tg.%s', $tg->getTelegram()->getGroup()->name);

        return join(' ', [
            $tg->trans(sprintf('icon.%s', $key), domain: $domain),
            $tg->trans(sprintf('command.%s', $key), domain: $domain),
        ]);
    }
}
