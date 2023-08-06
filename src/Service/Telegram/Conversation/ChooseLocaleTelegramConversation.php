<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Intl\Locale;
use App\Entity\Telegram\TelegramConversationState;
use App\Enum\Telegram\TelegramView;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Service\Telegram\TelegramLocaleSwitcher;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseLocaleTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_GUESS_LOCALE_ASKED = 10;
    public const STEP_LOCALE_ASKED = 20;
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

            $locales = $this->getGuessLocales($tg);

            if (count($locales) === 0) {
                return $this->askLocale($tg);
            }

            return $this->askGuessLocale($locales, $tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.locale.canceled'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_GUESS_LOCALE_ASKED) {
            return $this->gotGuessLocale($tg, $conversation);
        }

        if ($this->state->getStep() === self::STEP_LOCALE_ASKED) {
            return $this->gotLocale($tg, $conversation);
        }

        return null;
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return Locale[]
     */
    public function getGuessLocales(TelegramAwareHelper $tg): array
    {
        return $this->provider->getLocales(supported: true, country: $tg->getCountryCode());
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
        if (!$tg->getTelegram()->getMessengerUser()->isShowHints()) {
            return;
        }

        $localeCode = $tg->getLocaleCode();
        $locale = $localeCode === null ? null : $this->provider->getLocale($localeCode);

        $tg->replyView(TelegramView::LOCALE, [
            'locale' => $locale,
            'icon' => $locale === null ? null : $this->provider->getLocaleIcon($locale),
            'name' => $locale === null ? null : $this->provider->getLocaleName($locale),
        ]);
    }

    public function askGuessLocale(array $locales, TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_GUESS_LOCALE_ASKED);

        $keyboards = $this->getLocaleButtons($locales, $tg);
        $keyboards[] = $this->getOtherLocaleButton($tg);
        $keyboards[] = $this->getCancelButton($tg);

        return $tg->reply($this->getLocaleAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function gotGuessLocale(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getOtherLocaleButton($tg)->getText())) {
            return $this->askLocale($tg);
        }

        $locales = $this->getGuessLocales($tg);

        $locale = $this->getLocaleByButton($tg->getText(), $locales, $tg);

        if ($locale === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->askGuessLocale($locales, $tg);
        }

        $this->localeSwitcher->switchLocale($tg, $locale);

        $tg->replyOk($tg->trans('reply.locale.ok', [
            'icon' => $this->provider->getLocaleIcon($locale),
            'name' => $this->provider->getLocaleName($locale),
        ]));

        $tg->stopConversation($conversation);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function askLocale(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_LOCALE_ASKED);

        $keyboards = $this->getLocaleButtons($this->getLocales(), $tg);
        $keyboards[] = $this->getAbsentLocaleButton($tg);
        $keyboards[] = $this->getCancelButton($tg);

        return $tg->reply($this->getLocaleAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function gotLocale(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getAbsentLocaleButton($tg)->getText())) {
            $this->localeSwitcher->switchLocale($tg, null);

            $tg->stopConversation($conversation);

            return $this->chooseActionChatSender->sendActions($tg);
        }

        $locales = $this->getLocales();

        $locale = $this->getLocaleByButton($tg->getText(), $locales, $tg);

        if ($locale === null) {
            $tg->replyWrong($tg->trans('reply.wrong'));

            return $this->askLocale($tg);
        }

        $this->localeSwitcher->switchLocale($tg, $locale);

        $tg->replyOk($tg->trans('reply.locale.ok', [
            'icon' => $this->provider->getLocaleIcon($locale),
            'name' => $this->provider->getLocaleName($locale),
        ]));

        $tg->stopConversation($conversation);

        return $this->chooseActionChatSender->sendActions($tg);
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
        return $tg->button(join(' ', [
            $this->provider->getLocaleIcon($locale),
            $this->provider->getLocaleName($locale, $tg->getLocaleCode()),
        ]));
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

    public static function getLocaleAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('ask.locale.locale');
    }

    public static function getOtherLocaleButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', '🌎', $tg->trans('keyboard.other')));
    }

    public static function getAbsentLocaleButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(sprintf('%s %s', '🌎', $tg->trans('keyboard.absent')));
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }
}