<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Chat;

use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
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

    public function sendActions(TelegramAwareHelper $tg, string $text = null, bool $prependDefault = false): null
    {
        $tg->reply(
            $this->getQuery($tg, text: $text, prependDefault: $prependDefault),
            $this->getKeyboard($tg)
        );

        return null;
    }

    public function getQuery(TelegramAwareHelper $tg, string $text = null, bool $prependDefault = false): string
    {
        if ($text === null) {
            return $this->getActionQuery($tg);
        }

        if ($prependDefault) {
            return $text . ' ' . $this->getActionQuery($tg);
        }

        return $text;
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
            $buttons[] = $this->getCommandsButton($tg);
            $buttons[] = $this->getLimitsButton($tg);
            $buttons[] = $this->getPurgeButton($tg);
            $buttons[] = $this->getRestartButton($tg);
            $buttons[] = $this->getContactButton($tg);
            $buttons[] = $this->getShowLessButton($tg);
        } else {
            $buttons[] = $this->getShowMoreButton($tg);
        }

        return $tg->keyboard(...array_chunk($buttons, 2));
    }

    public function getActionQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.action');
    }

    public function getCreateButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('create'));
    }

    public function getSearchButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('search'));
    }

    public function getLookupButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('lookup'));
    }

    public function getSubscribeButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('subscribe'));
    }

    public function getSubscriptionsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser());
        $key = 'subscriptions';

        $text = $tg->trans($hasActiveSubscription ? $key : 'subscribe', domain: 'command_icon');
        $text .= ' ';
        $text .= $tg->trans($key, domain: 'command');

        return $tg->button($text);
    }

    public function getCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $countryCode = $tg->getCountryCode();
        $country = $countryCode === null ? null : $this->countryProvider->getCountry($countryCode);

        if ($country === null) {
            return $tg->button($tg->command('country'));
        }

        $text = $this->countryProvider->getCountryIcon($country);
        $text .= ' ';
        $text .= $tg->trans('country', domain: 'command');

        return $tg->button($text);
    }

    public function getLocaleButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $localeCode = $tg->getLocaleCode();
        $locale = $localeCode === null ? null : $this->localeProvider->getLocale($localeCode);

        if ($locale === null) {
            return $tg->button($tg->command('locale'));
        }

        $text = $this->localeProvider->getLocaleIcon($locale);
        $text .= ' ';
        $text .= $tg->trans('locale', domain: 'command');

        return $tg->button($text);
    }

    public function getLimitsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('limits'));
    }

    public function getPurgeButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('purge'));
    }

    public function getContactButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('contact'));
    }

    public function getCommandsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('commands'));
    }

    public function getRestartButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('restart'));
    }

    public function getShowMoreButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.more'));
    }

    public function getShowLessButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.less'));
    }
}
