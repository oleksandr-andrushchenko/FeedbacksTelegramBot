<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Conversation;

use App\Entity\Intl\Country;
use App\Entity\Location;
use App\Entity\Telegram\TelegramConversation as Entity;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Address\AddressLocalityUpserter;
use App\Service\Feedback\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Intl\CountryProvider;
use App\Service\AddressGeocoderInterface;
use App\Service\Telegram\Chat\BetterMatchBotTelegramChatSender;
use App\Service\Telegram\Conversation\TelegramConversation;
use App\Service\Telegram\Conversation\TelegramConversationInterface;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\TimezoneGeocoderInterface;
use Longman\TelegramBot\Entities\KeyboardButton;

class CountryTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CHANGE_CONFIRM_QUERIED = 5;
    public const STEP_REQUEST_LOCATION_QUERIED = 7;
    public const STEP_GUESS_COUNTRY_QUERIED = 10;
    public const STEP_COUNTRY_QUERIED = 20;
    public const STEP_TIMEZONE_QUERIED = 30;
    public const STEP_CANCEL_PRESSED = 40;

    public function __construct(
        private readonly CountryProvider $provider,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly BetterMatchBotTelegramChatSender $betterMatchBotSender,
        private readonly AddressGeocoderInterface $addressGeocoder,
        private readonly TimezoneGeocoderInterface $timezoneGeocoder,
        private readonly AddressLocalityUpserter $addressLocalityUpserter,
        private readonly bool $requestLocationStep,
    )
    {
        parent::__construct(new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CHANGE_CONFIRM_QUERIED => $this->gotChangeConfirm($tg, $entity),
            self::STEP_REQUEST_LOCATION_QUERIED => $this->gotRequestLocation($tg, $entity),
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

    public function getChangeConfirmQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $message = $this->getCurrentReply($tg);
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

    public function queryCustomLocation(TelegramAwareHelper $tg): null
    {
        $countries = $this->getGuessCountries($tg);

        if (count($countries) === 0) {
            return $this->queryCountry($tg);
        }

        return $this->queryGuessCountry($tg);
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

        if ($this->requestLocationStep) {
            return $this->queryRequestLocation($tg);
        }

        return $this->queryCustomLocation($tg);
    }

    public function getRequestLocationButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📍 ' . $tg->trans('keyboard.request_location', domain: 'country'), requestLocation: true);
    }

    public function getCustomLocationButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.custom_location', domain: 'country'));
    }

    public function getRequestLocationQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.request_location', domain: 'country');

        if ($help) {
            $query = $tg->view('country_request_location_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryRequestLocation(TelegramAwareHelper $tg, bool $help = false): ?string
    {
        $this->state->setStep(self::STEP_REQUEST_LOCATION_QUERIED);

        $message = $this->getRequestLocationQuery($tg, $help);

        $buttons = [];
        $buttons[] = $this->getRequestLocationButton($tg);
        $buttons[] = $this->getCustomLocationButton($tg);

        if ($this->state->hasNotSkipHelpButton('request_location')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotRequestLocation(TelegramAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('request_location');

            return $this->queryRequestLocation($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($tg->matchText($this->getCustomLocationButton($tg)->getText())) {
            return $this->queryCustomLocation($tg);
        }

        $locationResponse = $tg->getLocation();

        if ($locationResponse === null) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryRequestLocation($tg);
        }

        $user = $tg->getTelegram()->getMessengerUser()->getUser();
        $location = new Location($locationResponse->getLatitude(), $locationResponse->getLongitude());
        $user->setLocation($location);

        $address = $this->addressGeocoder->addressGeocode($user->getLocation());

        if ($address === null) {
            // todo: change message
            $message = $tg->trans('reply.request_location_failed', domain: 'country');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryCustomLocation($tg);
        }

        $addressLocality = $this->addressLocalityUpserter->upsertAddressLocality($address);

        $user
            ->setCountryCode($addressLocality->getCountryCode())
            ->setAddressLocality($addressLocality)
            ->setTimezone($addressLocality->getTimezone())
        ;

        $timezone = $user->getTimezone() ?? $this->timezoneGeocoder->timezoneGeocode($user->getLocation());

        if ($timezone === null) {
            // todo: change message
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryTimezone($tg);
        }

        $user
            ->setTimezone($timezone)
        ;

        return $this->replyAndClose($tg, $entity);
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

        $buttons = [];
        $buttons = array_merge($buttons, $this->getCountryButtons($this->getGuessCountries($tg), $tg));
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

        $buttons = [];
        $buttons = array_merge($buttons, $this->getCountryButtons($this->getCountries(), $tg));

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

        $user = $tg->getTelegram()->getMessengerUser()?->getUser();

        $user
            ->setCountryCode($country->getCode())
            ->setAddressLocality(null)
            ->setTimezone($country->getTimezones()[0])
        ;

        $timezones = $this->getTimezones($tg);

        if (count($timezones) > 1) {
            return $this->queryTimezone($tg);
        }

        $user
            ->setTimezone($timezones[0] ?? null)
        ;

        return $this->replyAndClose($tg, $entity);
    }

    public function getCurrentReply(TelegramAwareHelper $tg): string
    {
        $domain = 'country';
        $user = $tg->getTelegram()->getMessengerUser()->getUser();

        $countryCode = $tg->getCountryCode();
        $country = $countryCode === null ? null : $this->provider->getCountry($countryCode);
        $countryName = sprintf('<u>%s</u>', $this->provider->getCountryComposeName($country));
        $parameters = [
            'country' => $countryName,
        ];
        $message = $tg->trans('reply.current_country', $parameters, domain: $domain);

        $addressLocality = $user->getAddressLocality();

        if ($addressLocality !== null) {
            $message .= "\n";
            $regionName = sprintf(
                '<u>%s, %s</u>',
                $addressLocality->getRegion2(),
                $addressLocality->getRegion1()
            );
            $parameters = [
                'region' => $regionName,
            ];
            $message .= $tg->trans('reply.current_region', $parameters, domain: $domain);

            $message .= "\n";
            $localityName = sprintf('<u>%s</u>', $addressLocality->getLocality());
            $parameters = [
                'locality' => $localityName,
            ];
            $message .= $tg->trans('reply.current_locality', $parameters, domain: $domain);
        }

        $message .= "\n";
        $timezone = $tg->getTimezone() ?? $tg->trans('reply.unknown_timezone', domain: $domain);
        $timezoneName = sprintf('<u>%s</u>', $timezone);
        $parameters = [
            'timezone' => $timezoneName,
        ];
        $message .= $tg->trans('reply.current_timezone', $parameters, domain: $domain);

        return $message;
    }

    public function replyAndClose(TelegramAwareHelper $tg, Entity $entity): null
    {
        $tg->stopConversation($entity);

        $message = $tg->trans('reply.ok', domain: 'country');
        $message = $tg->okText($message);
        $message .= "\n\n";
        $message .= $this->getCurrentReply($tg);

        $this->chooseActionChatSender->sendActions($tg, $message);

        $keyboard = $this->chooseActionChatSender->getKeyboard($tg);
        $this->betterMatchBotSender->sendBetterMatchBotIfNeed($tg, $keyboard);

        return null;
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