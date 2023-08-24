<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Service\Feedback\FeedbackSubscriptionManager;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\TelegramAwareHelper;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseActionTelegramChatSender
{
    public function __construct(
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
    )
    {
    }

    public function sendActions(TelegramAwareHelper $tg, string $text = null): null
    {
        return $tg->reply(
            $this->getQuery($tg, text: $text),
            $this->getKeyboard($tg)
        )->null();
    }

    public function getQuery(TelegramAwareHelper $tg, string $text = null): string
    {
        return $text ?? $this->getActionQuery($tg);
    }

    public function getKeyboard(TelegramAwareHelper $tg): Keyboard
    {
        $buttons = [];
        $buttons[] = $this->getCreateButton($tg);
        $buttons[] = $this->getSearchButton($tg);
        $buttons[] = $this->getLookupButton($tg);

        $messengerUser = $tg->getTelegram()->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        if ($tg->getTelegram()->getBot()->acceptPayments() && !$hasActiveSubscription) {
            $buttons[] = $this->getSubscribeButton($tg);
        } elseif ($this->subscriptionManager->hasSubscription($messengerUser)) {
            $buttons[] = $this->getSubscriptionsButton($tg);
        }

        if ($messengerUser?->showExtendedKeyboard()) {
            $buttons[] = $this->getCountryButton($tg);
            $buttons[] = $this->getLocaleButton($tg);
            $buttons[] = $this->getHintsButton($tg);
            $buttons[] = $this->getPurgeButton($tg);
            $buttons[] = $this->getContactButton($tg);
            $buttons[] = $this->getCommandsButton($tg);
            $buttons[] = $this->getRestartButton($tg);
            $buttons[] = $this->getShowLessButton($tg);
        } else {
            $buttons[] = $this->getShowMoreButton($tg);
        }

        return $tg->keyboard(...array_chunk($buttons, 2));
    }

    public static function getActionQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.action');
    }

    public static function getCreateButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('create'));
    }

    public static function getSearchButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('search'));
    }

    public function getLookupButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $locked = !$this->subscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser());

        return $tg->button($tg->command('lookup', $locked));
    }

    public static function getSubscribeButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('subscribe'));
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
        $countryCode = $tg->getCountryCode();
        $country = $countryCode === null ? null : $this->countryProvider->getCountry($countryCode);

        if ($country === null) {
            return $tg->button($tg->command('country'));
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
            return $tg->button($tg->command('locale'));
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
        return $tg->button($tg->command('purge'));
    }

    public static function getContactButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('contact'));
    }

    public static function getCommandsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('commands'));
    }

    public static function getRestartButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('restart'));
    }

    public static function getShowMoreButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.more'));
    }

    public static function getShowLessButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.less'));
    }
}
