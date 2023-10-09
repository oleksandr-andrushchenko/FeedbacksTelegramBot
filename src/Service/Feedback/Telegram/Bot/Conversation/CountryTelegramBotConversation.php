<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Intl\Country;
use App\Entity\Location;
use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Service\Address\AddressProvider;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\Bot\Chat\TelegramBotMatchesChatSender;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use DateTimeImmutable;
use Longman\TelegramBot\Entities\KeyboardButton;

class CountryTelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
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
        private readonly TelegramBotMatchesChatSender $betterMatchBotSender,
        private readonly AddressProvider $addressProvider,
        private readonly bool $requestLocationStep,
    )
    {
        parent::__construct(new TelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
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

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        return $this->queryChangeConfirm($tg);
    }

    public function getGuessCountries(TelegramBotAwareHelper $tg): array
    {
        return $this->provider->getCountries($tg->getLocaleCode());
    }

    public function getCountries(): array
    {
        return $this->provider->getCountries();
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $message = $tg->trans('reply.canceled', domain: 'country');
        $message .= "\n\n";
        $message .= $this->getCurrentReply($tg);
        $message = $tg->upsetText($message);

        $tg->stopConversation($entity)->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function getChangeConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
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

    public function queryChangeConfirm(TelegramBotAwareHelper $tg, bool $help = false): ?string
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

    public function queryCustomLocation(TelegramBotAwareHelper $tg): null
    {
        $countries = $this->getGuessCountries($tg);

        if (count($countries) === 0) {
            return $this->queryCountry($tg);
        }

        return $this->queryGuessCountry($tg);
    }

    public function gotChangeConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
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

    public function getRequestLocationButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button('📍 ' . $tg->trans('keyboard.request_location', domain: 'country'), requestLocation: true);
    }

    public function getCustomLocationButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.custom_location', domain: 'country'));
    }

    public function getRequestLocationQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.request_location', domain: 'country');

        if ($help) {
            $query = $tg->view('country_request_location_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryRequestLocation(TelegramBotAwareHelper $tg, bool $help = false): ?string
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

    public function gotRequestLocation(TelegramBotAwareHelper $tg, Entity $entity): null
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

        $user = $tg->getBot()->getMessengerUser()->getUser();
        $location = new Location($locationResponse->getLatitude(), $locationResponse->getLongitude());
        $user->setLocation($location);

        $address = $this->addressProvider->getAddress($user->getLocation());

        if ($address === null) {
            $message = $tg->trans('reply.request_location_failed', domain: 'country');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryCustomLocation($tg);
        }

        $address->incCount();
        $address->setUpdatedAt(new DateTimeImmutable());

        $user
            ->setCountryCode($address->getCountryCode())
            ->setAddress($address)
            ->setTimezone($address->getTimezone())
        ;

        if ($user->getTimezone() === null) {
            return $this->queryTimezone($tg);
        }

        return $this->replyAndClose($tg, $entity);
    }

    public function getCountryQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.country', domain: 'country');

        if ($help) {
            $query = $tg->view('country_country_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryGuessCountry(TelegramBotAwareHelper $tg, bool $help = false): null
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

    public function queryCountry(TelegramBotAwareHelper $tg, bool $help = false): null
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

    public function gotCountry(TelegramBotAwareHelper $tg, Entity $entity, bool $guess): null
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

        $user = $tg->getBot()->getMessengerUser()?->getUser();

        $user
            ->setCountryCode($country->getCode())
            ->setAddress(null)
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

    public function getCurrentReply(TelegramBotAwareHelper $tg): string
    {
        $domain = 'country';
        $user = $tg->getBot()->getMessengerUser()->getUser();

        $countryCode = $tg->getCountryCode();
        $country = $countryCode === null ? null : $this->provider->getCountry($countryCode);
        $countryName = sprintf('<u>%s</u>', $this->provider->getCountryComposeName($country));
        $parameters = [
            'country' => $countryName,
        ];
        $message = $tg->trans('reply.current_country', $parameters, domain: $domain);

        $address = $user->getAddress();

        if ($address !== null) {
            $message .= "\n";
            $regionName = sprintf(
                '<u>%s</u>',
                implode(', ', array_filter([
                    $address->getAdministrativeAreaLevel3(),
                    $address->getAdministrativeAreaLevel2(),
                    $address->getAdministrativeAreaLevel1(),
                ]))
            );
            $parameters = [
                'region' => $regionName,
            ];
            $message .= $tg->trans('reply.current_region', $parameters, domain: $domain);
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

    public function replyAndClose(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $tg->stopConversation($entity);

        $message = $tg->trans('reply.ok', domain: 'country');
        $message = $tg->okText($message);
        $message .= "\n\n";
        $message .= $this->getCurrentReply($tg);

        $this->chooseActionChatSender->sendActions($tg, $message);

        $keyboard = $this->chooseActionChatSender->getKeyboard($tg);
        $this->betterMatchBotSender->sendTelegramBotMatchesIfNeed($tg, $keyboard);

        return null;
    }

    public function getTimezoneQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.timezone', domain: 'country');

        if ($help) {
            $query = $tg->view('country_timezone_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryTimezone(TelegramBotAwareHelper $tg, bool $help = false): null
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

    public function gotTimezone(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('timezone');

            return $this->queryTimezone($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            $user = $tg->getBot()->getMessengerUser()?->getUser();
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

        $tg->getBot()->getMessengerUser()?->getUser()->setTimezone($timezone);

        return $this->replyAndClose($tg, $entity);
    }

    /**
     * @param Country[]|null $countries
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getCountryButtons(array $countries, TelegramBotAwareHelper $tg): array
    {
        return array_map(fn (Country $country) => $this->getCountryButton($country, $tg), $countries);
    }

    public function getCountryButton(Country $country, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->provider->getCountryComposeName($country));
    }

    public function getCountryByButton(?string $button, array $countries, TelegramBotAwareHelper $tg): ?Country
    {
        foreach ($countries as $country) {
            if ($this->getCountryButton($country, $tg)->getText() === $button) {
                return $country;
            }
        }

        return null;
    }

    public function getOtherCountryButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        $icon = $this->provider->getUnknownCountryIcon();
        $name = $tg->trans('keyboard.other');

        return $tg->button($icon . ' ' . $name);
    }

    public function getTimezones(TelegramBotAwareHelper $tg): array
    {
        $country = $this->provider->getCountry($tg->getCountryCode());

        return $country->getTimezones();
    }

    /**
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getTimezoneButtons(TelegramBotAwareHelper $tg): array
    {
        return array_map(fn (string $timezone) => $this->getTimezoneButton($timezone, $tg), $this->getTimezones($tg));
    }

    public function getTimezoneButton(string $timezone, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($timezone);
    }

    public function getTimezoneByButton(string $button, TelegramBotAwareHelper $tg): ?string
    {
        foreach ($this->getTimezones($tg) as $timezone) {
            if ($this->getTimezoneButton($timezone, $tg)->getText() === $button) {
                return $timezone;
            }
        }

        return null;
    }
}