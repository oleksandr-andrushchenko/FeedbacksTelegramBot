<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Conversation;

use App\Entity\Intl\Country;
use App\Entity\Telegram\TelegramConversation as Entity;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Feedback\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramConversation;
use App\Service\Telegram\TelegramConversationInterface;
use Longman\TelegramBot\Entities\KeyboardButton;

class CountryTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CHANGE_CONFIRM_QUERIED = 5;
    public const STEP_GUESS_COUNTRY_QUERIED = 10;
    public const STEP_COUNTRY_QUERIED = 20;
    public const STEP_TIMEZONE_QUERIED = 30;
    public const STEP_CANCEL_PRESSED = 40;

    public function __construct(
        private readonly CountryProvider $provider,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
    )
    {
        parent::__construct(new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CHANGE_CONFIRM_QUERIED => $this->gotChangeConfirm($tg, $entity),
            self::STEP_GUESS_COUNTRY_QUERIED => $this->gotCountry($tg, $entity, true),
            self::STEP_COUNTRY_QUERIED => $this->gotCountry($tg, $entity, false),
            self::STEP_TIMEZONE_QUERIED => $this->gotTimezone($tg, $entity),
        };
    }

    public function start(TelegramAwareHelper $tg): ?string
    {
        return $this->queryChangeConfirm($tg);
    }

    public function getGuessCountries(TelegramAwareHelper $tg): array
    {
        return $this->provider->getCountries($tg->getLocaleCode());
    }

    public function getCountries(): array
    {
        return $this->provider->getCountries();
    }

    public function gotCancel(TelegramAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $message = $tg->trans('reply.canceled', domain: 'country');
        $message .= "\n\n";
        $message .= $this->getCurrentReply($tg);
        $message = $tg->upsetText($message);

        $tg->stopConversation($entity)->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function getCurrentCountryReply(TelegramAwareHelper $tg): string
    {
        $countryCode = $tg->getCountryCode();
        $country = $countryCode === null ? null : $this->provider->getCountry($countryCode);

        $countryName = sprintf('<u>%s</u>', $this->provider->getCountryComposeName($country));
        $parameters = [
            'country' => $countryName,
        ];

        return $tg->trans('reply.current_country', $parameters, domain: 'country');
    }

    public function getCurrentTimezoneReply(TelegramAwareHelper $tg): string
    {
        $timezone = $tg->getTimezone() ?? $tg->trans('reply.unknown_timezone', domain: 'country');
        $timezoneName = sprintf('<u>%s</u>', $timezone);
        $parameters = [
            'timezone' => $timezoneName,
        ];

        return $tg->trans('reply.current_timezone', $parameters, domain: 'country');
    }

    public function getChangeConfirmQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $message = $this->getCurrentCountryReply($tg);
        $message .= "\n";
        $message .= $this->getCurrentTimezoneReply($tg);
        $message .= "\n\n";

        $query = $tg->trans('query.change_confirm', domain: 'country');

        if ($help) {
            $query = $tg->view('country_change_confirm_help', [
                'query' => $query,
            ]);
        }

        $message .= $query;

        return $message;
    }

    public function queryChangeConfirm(TelegramAwareHelper $tg, bool $help = false): ?string
    {
        $this->state->setStep(self::STEP_CHANGE_CONFIRM_QUERIED);

        $message = $this->getChangeConfirmQuery($tg, $help);

        $buttons = [
            $tg->yesButton(),
            $tg->noButton(),
        ];

        if ($this->state->hasNotSkipHelpButton('change_confirm')) {
            $buttons[] = $tg->helpButton();
        }

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotChangeConfirm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('change_confirm');

            return $this->queryChangeConfirm($tg, true);
        }
        if (!$tg->matchText($tg->yesButton()->getText())) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryChangeConfirm($tg);
        }

        $countries = $this->getGuessCountries($tg);

        if (count($countries) === 0) {
            return $this->queryCountry($tg);
        }

        return $this->queryGuessCountry($tg);
    }

    public function getCountryQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.country', domain: 'country');

        if ($help) {
            $query = $tg->view('country_country_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryGuessCountry(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_GUESS_COUNTRY_QUERIED);

        $message = $this->getCountryQuery($tg, $help);

        $buttons = $this->getCountryButtons($this->getGuessCountries($tg), $tg);
        $buttons[] = $this->getOtherCountryButton($tg);

        if ($this->state->hasNotSkipHelpButton('guess_country')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function queryCountry(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_COUNTRY_QUERIED);

        $message = $this->getCountryQuery($tg, $help);

        $buttons = $this->getCountryButtons($this->getCountries(), $tg);

        if ($this->state->hasNotSkipHelpButton('country')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotCountry(TelegramAwareHelper $tg, Entity $entity, bool $guess): null
    {
        if ($tg->matchText($tg->helpButton()->getText())) {
            if ($guess) {
                $this->state->addSkipHelpButton('guess_country');

                return $this->queryGuessCountry($tg, true);
            }

            $this->state->addSkipHelpButton('country');

            return $this->queryCountry($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($guess && $tg->matchText($this->getOtherCountryButton($tg)->getText())) {
            return $this->queryCountry($tg);
        }

        if ($tg->matchText(null)) {
            $country = null;
        } else {
            $countries = $guess ? $this->getGuessCountries($tg) : $this->getCountries();
            $country = $this->getCountryByButton($tg->getText(), $countries, $tg);
        }

        if ($country === null) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $guess ? $this->queryGuessCountry($tg) : $this->queryCountry($tg);
        }

        if ($country->getCode() !== $tg->getCountryCode()) {
            $tg->getTelegram()->getMessengerUser()?->getUser()
                ->setCountryCode($country->getCode())
            ;
        }

        $timezones = $this->getTimezones($tg);

        if (count($timezones) > 1) {
            return $this->queryTimezone($tg);
        }

        $tg->getTelegram()->getMessengerUser()?->getUser()->setTimezone($timezones[0] ?? null);

        return $this->replyAndClose($tg, $entity);
    }

    public function getCurrentReply(TelegramAwareHelper $tg): string
    {
        $message = $this->getCurrentCountryReply($tg);
        $message .= "\n";
        $message .= $this->getCurrentTimezoneReply($tg);

        return $message;
    }

    public function replyAndClose(TelegramAwareHelper $tg, Entity $entity): null
    {
        $tg->stopConversation($entity);

        $message = $tg->trans('reply.ok', domain: 'country');
        $message = $tg->okText($message);
        $message .= "\n\n";
        $message .= $this->getCurrentReply($tg);

        return $this->chooseActionChatSender->sendActions($tg, $message);
    }

    public function getTimezoneQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.timezone', domain: 'country');

        if ($help) {
            $query = $tg->view('country_timezone_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryTimezone(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_TIMEZONE_QUERIED);

        $message = $this->getTimezoneQuery($tg, $help);
        $buttons = $this->getTimezoneButtons($tg);

        if ($this->state->hasNotSkipHelpButton('timezone')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotTimezone(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('timezone');

            return $this->queryTimezone($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            $user = $tg->getTelegram()->getMessengerUser()?->getUser();
            $country = $this->provider->getCountry($user->getCountryCode());
            $user->setTimezone($country?->getTimezones()[0] ?? null);

            return $this->gotCancel($tg, $entity);
        }

        if ($tg->matchText(null)) {
            $timezone = null;
        } else {
            $timezone = $this->getTimezoneByButton($tg->getText(), $tg);
        }

        if ($timezone === null) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryTimezone($tg);
        }

        $tg->getTelegram()->getMessengerUser()?->getUser()->setTimezone($timezone);

        return $this->replyAndClose($tg, $entity);
    }

    /**
     * @param Country[]|null $countries
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getCountryButtons(array $countries, TelegramAwareHelper $tg): array
    {
        return array_map(fn (Country $country) => $this->getCountryButton($country, $tg), $countries);
    }

    public function getCountryButton(Country $country, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->provider->getCountryComposeName($country));
    }

    public function getCountryByButton(?string $button, array $countries, TelegramAwareHelper $tg): ?Country
    {
        foreach ($countries as $country) {
            if ($this->getCountryButton($country, $tg)->getText() === $button) {
                return $country;
            }
        }

        return null;
    }

    public function getOtherCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $icon = $this->provider->getUnknownCountryIcon();
        $name = $tg->trans('keyboard.other');

        return $tg->button($icon . ' ' . $name);
    }

    public function getTimezones(TelegramAwareHelper $tg): array
    {
        $country = $this->provider->getCountry($tg->getCountryCode());

        return $country->getTimezones();
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getTimezoneButtons(TelegramAwareHelper $tg): array
    {
        return array_map(fn (string $timezone) => $this->getTimezoneButton($timezone, $tg), $this->getTimezones($tg));
    }

    public function getTimezoneButton(string $timezone, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($timezone);
    }

    public function getTimezoneByButton(string $button, TelegramAwareHelper $tg): ?string
    {
        foreach ($this->getTimezones($tg) as $timezone) {
            if ($this->getTimezoneButton($timezone, $tg)->getText() === $button) {
                return $timezone;
            }
        }

        return null;
    }
}