<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Intl\Country;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseFeedbackCountryTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_GUESS_COUNTRY_ASKED = 10;
    public const STEP_COUNTRY_ASKED = 20;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly CountryProvider $provider,
    )
    {
        parent::__construct($awareHelper, new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $countries = $this->provider->getCountries($tg->getLanguageCode());

            if (count($countries) === 0) {
                return $this->askCountry($tg);
            }

            return $this->askGuessCountry($countries, $tg);
        }

        if ($this->state->getStep() === self::STEP_GUESS_COUNTRY_ASKED) {
            return $this->onGuessCountryAnswer($tg, $conversation);
        }

        if ($this->state->getStep() === self::STEP_COUNTRY_ASKED) {
            return $this->onCountryAnswer($tg, $conversation);
        }

        return null;
    }

    public function askGuessCountry(array $countries, TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_GUESS_COUNTRY_ASKED);

        $keyboards = $this->getCountryButtons($countries, $tg);
        $keyboards[] = $this->getOtherCountryButton($tg);

        return $tg->reply($this->getCountryAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function onGuessCountryAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getOtherCountryButton($tg)->getText())) {
            return $this->askCountry($tg);
        }

        $countries = $this->provider->getCountries($tg->getLanguageCode());

        $country = $this->getCountryByButton($tg->getText(), $countries, $tg);

        if ($country === null) {
            $tg->replyWrong();

            return $this->askGuessCountry($countries, $tg);
        }

        $tg->getTelegram()->getMessengerUser()->getUser()->setCountryCode($country->getCode());

        return $tg->stopConversation($conversation)
            ->startConversation(ChooseFeedbackActionTelegramConversation::class)
            ->null()
        ;
    }

    public function askCountry(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_COUNTRY_ASKED);

        $keyboards = $this->getCountryButtons($this->provider->getCountries(), $tg);
        $keyboards[] = $this->getAbsentCountryButton($tg);

        return $tg->reply($this->getCountryAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function onCountryAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getAbsentCountryButton($tg)->getText())) {
            return $tg->stopConversation($conversation)
                ->startConversation(ChooseFeedbackActionTelegramConversation::class)
                ->null()
            ;
        }

        $countries = $this->provider->getCountries();

        $country = $this->getCountryByButton($tg->getText(), $countries, $tg);

        if ($country === null) {
            $tg->replyWrong();

            return $this->askCountry($tg);
        }

        $tg->getTelegram()->getMessengerUser()->getUser()->setCountryCode($country->getCode());

        return $tg->stopConversation($conversation)
            ->startConversation(ChooseFeedbackActionTelegramConversation::class)
            ->null()
        ;
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
        return $tg->button('feedbacks.keyboard.choose_country.country', [
            'icon' => $this->provider->getCountryIcon($country),
            'name' => $this->provider->getCountryName($country, $tg->getLanguageCode()),
        ]);
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
        return $tg->trans('feedbacks.ask.choose_country.country');
    }

    public static function getOtherCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('feedbacks.keyboard.choose_country.other');
    }

    public static function getAbsentCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('feedbacks.keyboard.choose_country.absent');
    }
}