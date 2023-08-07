<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Intl\Country;
use App\Entity\Telegram\TelegramConversationState;
use App\Enum\Telegram\TelegramView;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseCountryTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_GUESS_COUNTRY_ASKED = 10;
    public const STEP_COUNTRY_ASKED = 20;
    public const STEP_CANCEL_PRESSED = 30;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly CountryProvider $provider,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
    )
    {
        parent::__construct($awareHelper, new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            $countries = $this->getGuessCountries($tg);

            if (count($countries) === 0) {
                return $this->askCountry($tg);
            }

            return $this->askGuessCountry($countries, $tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.country.canceled'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_GUESS_COUNTRY_ASKED) {
            return $this->gotGuessCountry($tg, $conversation);
        }

        if ($this->state->getStep() === self::STEP_COUNTRY_ASKED) {
            return $this->gotCountry($tg, $conversation);
        }

        return null;
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
        if (!$tg->getTelegram()->getMessengerUser()->isShowHints()) {
            return;
        }

        $countryCode = $tg->getCountryCode();
        $country = $countryCode === null ? null : $this->provider->getCountry($countryCode);

        $tg->replyView(TelegramView::COUNTRY, [
            'country' => $country,
            'icon' => $country === null ? null : $this->provider->getCountryIcon($country),
            'name' => $country === null ? null : $this->provider->getCountryName($country),
        ]);
    }

    public function askGuessCountry(array $countries, TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_GUESS_COUNTRY_ASKED);

        $keyboards = $this->getCountryButtons($countries, $tg);
        $keyboards[] = $this->getOtherCountryButton($tg);
        $keyboards[] = $this->getCancelButton($tg);

        return $tg->reply($this->getCountryAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function gotGuessCountry(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getOtherCountryButton($tg)->getText())) {
            return $this->askCountry($tg);
        }

        $countries = $this->getGuessCountries($tg);

        $country = $this->getCountryByButton($tg->getText(), $countries, $tg);

        if ($country === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->askGuessCountry($countries, $tg);
        }

        $tg->getTelegram()->getMessengerUser()->getUser()->setCountryCode($country->getCode());

        $tg->replyOk($tg->trans('reply.country.ok', [
            'icon' => $this->provider->getCountryIcon($country),
            'name' => $this->provider->getCountryName($country),
        ]));

        $tg->stopConversation($conversation);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function askCountry(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_COUNTRY_ASKED);

        $keyboards = $this->getCountryButtons($this->provider->getCountries(), $tg);
        $keyboards[] = $this->getAbsentCountryButton($tg);
        $keyboards[] = $this->getCancelButton($tg);

        return $tg->reply($this->getCountryAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function gotCountry(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getAbsentCountryButton($tg)->getText())) {
            $tg->getTelegram()->getMessengerUser()->getUser()->setCountryCode(null);

            $tg->stopConversation($conversation);

            return $this->chooseActionChatSender->sendActions($tg);
        }

        $countries = $this->getCountries();

        $country = $this->getCountryByButton($tg->getText(), $countries, $tg);

        if ($country === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->askCountry($tg);
        }

        $tg->getTelegram()->getMessengerUser()->getUser()->setCountryCode($country->getCode());

        $tg->replyOk($tg->trans('reply.country.ok', [
            'icon' => $this->provider->getCountryIcon($country),
            'name' => $this->provider->getCountryName($country),
        ]));

        $tg->stopConversation($conversation);

        return $this->chooseActionChatSender->sendActions($tg);
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
        return $tg->button(join(' ', [
            $this->provider->getCountryIcon($country),
            $this->provider->getCountryName($country, $tg->getLocaleCode()),
        ]));
    }

    public function getCountryByButton(string $button, array $countries, TelegramAwareHelper $tg): ?Country
    {
        foreach ($countries as $country) {
            if ($this->getCountryButton($country, $tg)->getText() === $button) {
                return $country;
            }
        }

        return null;
    }

    public static function getCountryAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('ask.country.country');
    }

    public static function getOtherCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', '🌎', $tg->trans('keyboard.other')));
    }

    public static function getAbsentCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', '🌎', $tg->trans('keyboard.absent')));
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }
}