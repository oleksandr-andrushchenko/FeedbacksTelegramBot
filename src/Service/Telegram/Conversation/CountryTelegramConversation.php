<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Intl\Country;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Entity;
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
        $this->describe($tg);

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

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $tg->reply($tg->view('describe_country'));
    }

    public function gotCancel(TelegramAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity)->replyUpset($tg->trans('reply.canceled', domain: 'tg.country'));

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function getCurrentCountryReply(TelegramAwareHelper $tg): string
    {
        $countryCode = $tg->getCountryCode();
        $country = $countryCode === null ? null : $this->provider->getCountry($countryCode);

        return $tg->trans(
            'reply.current_country',
            [
                'country' => sprintf('<u>%s</u>', $this->provider->getComposeCountryName($country)),
            ],
            domain: 'tg.country'
        );
    }

    public function getCurrentTimezoneReply(TelegramAwareHelper $tg): string
    {
        return $tg->trans(
            'reply.current_timezone',
            [
                'timezone' => sprintf('<u>%s</u>', $tg->getTimezone() ?? $tg->trans('reply.unknown_timezone', domain: 'tg.country')),
            ],
            domain: 'tg.country'
        );
    }

    public function queryChangeConfirm(TelegramAwareHelper $tg): ?string
    {
        $this->state->setStep(self::STEP_CHANGE_CONFIRM_QUERIED);

        $buttons = [];
        $buttons[] = $this->getChangeConfirmYesButton($tg);
        $buttons[] = $this->getChangeConfirmNoButton($tg);

        return $tg->reply(
            join(' ', [
                $this->getCurrentCountryReply($tg),
                $this->getCurrentTimezoneReply($tg),
                $this->getChangeConfirmQuery($tg),
            ]),
            $tg->keyboard(...$buttons)
        )->null();
    }

    public function gotChangeConfirm(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($this->getChangeConfirmNoButton($tg)->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
        if (!$tg->matchText($this->getChangeConfirmYesButton($tg)->getText())) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->queryChangeConfirm($tg);
        }

        $countries = $this->getGuessCountries($tg);

        if (count($countries) === 0) {
            return $this->queryCountry($tg);
        }

        return $this->queryGuessCountry($tg);
    }

    public function queryGuessCountry(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_GUESS_COUNTRY_QUERIED);

        $buttons = $this->getCountryButtons($this->getGuessCountries($tg), $tg);
        $buttons[] = $this->getOtherCountryButton($tg);
        $buttons[] = $this->getCancelButton($tg);

        return $tg->reply($this->getCountryQuery($tg), $tg->keyboard(...$buttons))->null();
    }

    public function queryCountry(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_COUNTRY_QUERIED);

        $buttons = $this->getCountryButtons($this->getCountries(), $tg);
        $buttons[] = $this->getCancelButton($tg);

        return $tg->reply($this->getCountryQuery($tg), $tg->keyboard(...$buttons))->null();
    }

    public function gotCountry(TelegramAwareHelper $tg, Entity $entity, bool $guess): null
    {
        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
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
            $tg->replyWrong($tg->trans('reply.wrong'));

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

    private function replyAndClose(TelegramAwareHelper $tg, Entity $entity): null
    {
        $tg->stopConversation($entity);

        $replyText = $tg->okText(join(' ', [
            $tg->trans('reply.ok', domain: 'tg.country'),
            $this->getCurrentCountryReply($tg),
            $this->getCurrentTimezoneReply($tg),
        ]));

        return $this->chooseActionChatSender->sendActions($tg, $replyText);
    }

    public function queryTimezone(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_TIMEZONE_QUERIED);

        $buttons = $this->getTimezoneButtons($tg);
        $buttons[] = $this->getCancelButton($tg);

        return $tg->reply($this->getTimezoneQuery($tg), $tg->keyboard(...$buttons))->null();
    }

    public function gotTimezone(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($tg->matchText(null)) {
            $timezone = null;
        } else {
            $timezone = $this->getTimezoneByButton($tg->getText(), $tg);
        }

        if ($timezone === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->queryTimezone($tg);
        }

        $tg->getTelegram()->getMessengerUser()?->getUser()->setTimezone($timezone);

        return $this->replyAndClose($tg, $entity);
    }

    public static function getChangeConfirmQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.change_confirm', domain: 'tg.country');
    }

    public static function getChangeConfirmYesButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.yes'));
    }

    public static function getChangeConfirmNoButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.no'));
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
        return $tg->button($this->provider->getComposeCountryName($country));
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

    public static function getCountryQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.country', domain: 'tg.country');
    }

    public function getOtherCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', $this->provider->getUnknownCountryIcon(), $tg->trans('keyboard.other')));
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

    public static function getTimezoneQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.timezone', domain: 'tg.country');
    }

    public function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }
}