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
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        if ($tg->getTelegram()->getBot()->acceptPayments() && !$hasActiveSubscription) {
            $keyboards[] = $this->getSubscribeButton($tg);
        } elseif ($this->subscriptionManager->hasSubscription($messengerUser)) {
            $keyboards[] = $this->getSubscriptionsButton($tg);
        }

        if ($messengerUser?->showExtendedKeyboard()) {
            $keyboards[] = $this->getCountryButton($tg);
            $keyboards[] = $this->getLocaleButton($tg);
            $keyboards[] = $this->getHintsButton($tg);
            $keyboards[] = $this->getPurgeButton($tg);
            $keyboards[] = $this->getCommandsButton($tg);
            $keyboards[] = $this->getRestartButton($tg);
            $keyboards[] = $this->getShowLessButton($tg);
        } else {
            $keyboards[] = $this->getShowMoreButton($tg);
        }

        $keyboards = array_chunk($keyboards, 2);

        return $tg->reply($this->getActionQuery($tg), $tg->keyboard(...$keyboards))->null();
    }

    public static function getActionQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.action');
    }

    public static function getCreateButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'create'));
    }

    public static function getSearchButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'search'));
    }

    public static function getSubscribeButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'subscribe'));
    }

    public function getSubscriptionsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser());
        $key = 'subscriptions';
        $domain = sprintf('tg.%s', $tg->getTelegram()->getBot()->getGroup()->name);

        return $tg->button(
            join(' ', [
                $tg->trans(sprintf('icon.%s', $hasActiveSubscription ? $key : 'subscribe'), domain: $domain),
                $tg->trans(sprintf('command.%s', $key), domain: $domain),
            ])
        );
    }

    public function getCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $countryCode = $tg->getTelegram()?->getMessengerUser()?->getCountryCode();
        $country = $countryCode === null ? null : $this->countryProvider->getCountry($countryCode);

        if ($country === null) {
            return $tg->button(self::command($tg, 'country'));
        }

        $domain = sprintf('tg.%s', $tg->getTelegram()->getBot()->getGroup()->name);

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

        $domain = sprintf('tg.%s', $tg->getTelegram()->getBot()->getGroup()->name);

        return $tg->button(
            join(' ', [
                $this->localeProvider->getLocaleIcon($locale),
                $tg->trans('command.locale', domain: $domain),
            ])
        );
    }

    public static function getHintsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $isShowHints = $tg->getTelegram()->getMessengerUser()->showHints();
        $domain = sprintf('tg.%s', $tg->getTelegram()->getBot()->getGroup()->name);

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

    public static function getContactButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'contact'));
    }

    public static function getCommandsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'commands'));
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
        $domain = sprintf('tg.%s', $tg->getTelegram()->getBot()->getGroup()->name);

        return join(' ', [
            $tg->trans(sprintf('icon.%s', $key), domain: $domain),
            $tg->trans(sprintf('command.%s', $key), domain: $domain),
        ]);
    }
}
