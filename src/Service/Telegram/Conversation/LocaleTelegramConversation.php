<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Intl\Locale;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Service\Telegram\TelegramLocaleSwitcher;
use Longman\TelegramBot\Entities\KeyboardButton;

class LocaleTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CHANGE_CONFIRM_QUERIED = 5;
    public const STEP_GUESS_LOCALE_QUERIED = 10;
    public const STEP_LOCALE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly LocaleProvider $provider,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly TelegramLocaleSwitcher $localeSwitcher,
    )
    {
        parent::__construct($awareHelper, new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            return $this->queryChangeConfirm($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($this->state->getStep() === self::STEP_CHANGE_CONFIRM_QUERIED) {
            return $this->gotChangeConfirm($tg, $conversation);
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.canceled', domain: 'tg.locale'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_GUESS_LOCALE_QUERIED) {
            return $this->gotLocale($tg, $conversation, true);
        }

        if ($this->state->getStep() === self::STEP_LOCALE_QUERIED) {
            return $this->gotLocale($tg, $conversation, false);
        }

        return null;
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return Locale[]
     */
    public function getGuessLocales(TelegramAwareHelper $tg): array
    {
        return $this->provider->getLocales(supported: true, countryCode: $tg->getCountryCode());
    }

    /**
     * @return Locale[]
     */
    public function getLocales(): array
    {
        return $this->provider->getLocales(supported: true);
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $localeCode = $tg->getLocaleCode();
        $locale = $localeCode === null ? null : $this->provider->getLocale($localeCode);

        $tg->reply($tg->view('describe_locale', [
            'locale' => $locale,
            'icon' => $locale === null ? null : $this->provider->getLocaleIcon($locale),
            'name' => $locale === null ? null : $this->provider->getLocaleName($locale),
        ]));
    }

    public function getCurrentLocaleReply(TelegramAwareHelper $tg): string
    {
        $localeCode = $tg->getLocaleCode();
        $locale = $localeCode === null ? null : $this->provider->getLocale($localeCode);

        return $tg->trans(
            'reply.current_locale',
            [
                'locale' => sprintf('<u>%s</u>', $this->provider->getComposeLocaleName($locale)),
            ],
            domain: 'tg.locale'
        );
    }


    public function queryChangeConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_CHANGE_CONFIRM_QUERIED);

        $keyboards = [];
        $keyboards[] = $this->getChangeConfirmYesButton($tg);
        $keyboards[] = $this->getChangeConfirmNoButton($tg);

        return $tg->reply(
            join(' ', [
                $this->getCurrentLocaleReply($tg),
                $this->getChangeConfirmQuery($tg),
            ]),
            $tg->keyboard(...$keyboards)
        )->null();
    }

    public function gotChangeConfirm(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getChangeConfirmNoButton($tg)->getText())) {
            $tg->stopConversation($conversation);

            return $this->chooseActionChatSender->sendActions($tg);
        }

        $locales = $this->getGuessLocales($tg);

        if (count($locales) === 0) {
            return $this->queryLocale($tg);
        }

        return $this->queryGuessLocale($locales, $tg);
    }

    public function queryGuessLocale(array $locales, TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_GUESS_LOCALE_QUERIED);

        $keyboards = $this->getLocaleButtons($locales, $tg);
        $keyboards[] = $this->getOtherLocaleButton($tg);
        $keyboards[] = $this->getCancelButton($tg);

        return $tg->reply($this->getLocaleQuery($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function queryLocale(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_LOCALE_QUERIED);

        $keyboards = $this->getLocaleButtons($this->getLocales(), $tg);
        $keyboards[] = $this->getCancelButton($tg);

        return $tg->reply($this->getLocaleQuery($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function gotLocale(TelegramAwareHelper $tg, Conversation $conversation, bool $guess): null
    {
        if ($guess && $tg->matchText($this->getOtherLocaleButton($tg)->getText())) {
            return $this->queryLocale($tg);
        }

        $locales = $guess ? $this->getGuessLocales($tg) : $this->getLocales();
        $locale = $this->getLocaleByButton($tg->getText(), $locales, $tg);

        if ($locale === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $guess ? $this->queryGuessLocale($locales, $tg) : $this->queryLocale($tg);
        }

        $this->localeSwitcher->switchLocale($tg->getTelegram()->getMessengerUser(), $locale);

        $tg->replyOk(join(' ', [
            $tg->trans('reply.ok', domain: 'tg.locale'),
            $this->getCurrentLocaleReply($tg),
        ]));

        $tg->stopConversation($conversation);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public static function getChangeConfirmQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.change_confirm', domain: 'tg.locale');
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
     * @param Locale[]|null $locales
     * @param TelegramAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getLocaleButtons(array $locales, TelegramAwareHelper $tg): array
    {
        return array_map(fn (Locale $locale) => $this->getLocaleButton($locale, $tg), $locales);
    }

    public function getLocaleButton(Locale $locale, TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->provider->getComposeLocaleName($locale));
    }

    public function getLocaleByButton(string $button, array $locales, TelegramAwareHelper $tg): ?Locale
    {
        foreach ($locales as $locale) {
            if ($this->getLocaleButton($locale, $tg)->getText() === $button) {
                return $locale;
            }
        }

        return null;
    }

    public static function getLocaleQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.locale', domain: 'tg.locale');
    }

    public function getOtherLocaleButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', $this->provider->getUnknownLocaleIcon(), $tg->trans('keyboard.other')));
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }
}