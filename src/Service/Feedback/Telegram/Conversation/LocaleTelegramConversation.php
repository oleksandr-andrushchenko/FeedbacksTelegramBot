<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Conversation;

use App\Entity\Intl\Locale;
use App\Entity\Telegram\TelegramConversation as Entity;
use App\Entity\Telegram\TelegramConversationState;
use App\Service\Feedback\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Conversation\TelegramConversation;
use App\Service\Telegram\Conversation\TelegramConversationInterface;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramLocaleSwitcher;
use Longman\TelegramBot\Entities\KeyboardButton;

class LocaleTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CHANGE_CONFIRM_QUERIED = 5;
    public const STEP_GUESS_LOCALE_QUERIED = 10;
    public const STEP_LOCALE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;

    public function __construct(
        private readonly LocaleProvider $provider,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly TelegramLocaleSwitcher $localeSwitcher,
    )
    {
        parent::__construct(new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CHANGE_CONFIRM_QUERIED => $this->gotChangeConfirm($tg, $entity),
            self::STEP_GUESS_LOCALE_QUERIED => $this->gotLocale($tg, $entity, true),
            self::STEP_LOCALE_QUERIED => $this->gotLocale($tg, $entity, false),
        };
    }

    public function start(TelegramAwareHelper $tg): ?string
    {
        return $this->queryChangeConfirm($tg);
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

    public function gotCancel(TelegramAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $message = $tg->trans('reply.canceled', domain: 'locale');
        $message .= "\n\n";
        $message .= $this->getCurrentLocaleReply($tg);
        $message = $tg->upsetText($message);

        $tg->stopConversation($entity)->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function getCurrentLocaleReply(TelegramAwareHelper $tg): string
    {
        $localeCode = $tg->getLocaleCode();
        $locale = $localeCode === null ? null : $this->provider->getLocale($localeCode);
        $localeName = sprintf('<u>%s</u>', $this->provider->getLocaleComposeName($locale));
        $parameters = [
            'locale' => $localeName,
        ];

        return $tg->trans('reply.current_locale', $parameters, domain: 'locale');
    }

    public function getChangeConfirmQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $message = $this->getCurrentLocaleReply($tg);
        $message .= "\n\n";

        $query = $tg->trans('query.change_confirm', domain: 'locale');

        if ($help) {
            $query = $tg->view('locale_change_confirm_help', [
                'query' => $query,
            ]);
        }
        $message .= $query;

        return $message;
    }

    public function queryChangeConfirm(TelegramAwareHelper $tg, bool $help = false): null
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

        $locales = $this->getGuessLocales($tg);

        if (count($locales) === 0) {
            return $this->queryLocale($tg);
        }

        return $this->queryGuessLocale($tg);
    }

    public function getLocaleQuery(TelegramAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.locale', domain: 'locale');

        if ($help) {
            $query = $tg->view('locale_locale_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryGuessLocale(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_GUESS_LOCALE_QUERIED);

        $message = $this->getLocaleQuery($tg, $help);

        $buttons = $this->getLocaleButtons($this->getGuessLocales($tg), $tg);
        $buttons[] = $this->getOtherLocaleButton($tg);

        if ($this->state->hasNotSkipHelpButton('guess_locale')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function queryLocale(TelegramAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_LOCALE_QUERIED);

        $message = $this->getLocaleQuery($tg, $help);

        $buttons = $this->getLocaleButtons($this->getLocales(), $tg);

        if ($this->state->hasNotSkipHelpButton('locale')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getGotLocaleReply(TelegramAwareHelper $tg): string
    {
        $message = $tg->trans('reply.ok', domain: 'locale');
        $message = $tg->okText($message);
        $message .= "\n\n";
        $message .= $this->getCurrentLocaleReply($tg);

        return $message;
    }

    public function gotLocale(TelegramAwareHelper $tg, Entity $entity, bool $guess): null
    {
        if ($tg->matchText($tg->helpButton()->getText())) {
            if ($guess) {
                $this->state->addSkipHelpButton('guess_locale');

                return $this->queryGuessLocale($tg, true);
            }

            $this->state->addSkipHelpButton('locale');

            return $this->queryLocale($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }
        if ($guess && $tg->matchText($this->getOtherLocaleButton($tg)->getText())) {
            return $this->queryLocale($tg);
        }

        if ($tg->matchText(null)) {
            $locale = null;
        } else {
            $locales = $guess ? $this->getGuessLocales($tg) : $this->getLocales();
            $locale = $this->getLocaleByButton($tg->getText(), $locales, $tg);
        }

        if ($locale === null) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $guess ? $this->queryGuessLocale($tg) : $this->queryLocale($tg);
        }

        if ($locale->getCode() !== $tg->getLocaleCode()) {
            $tg->getTelegram()->getMessengerUser()->getUser()
                ->setLocaleCode($locale->getCode())
            ;
            $this->localeSwitcher->switchLocale($locale);
        }

        $tg->stopConversation($entity);

        $message = $this->getGotLocaleReply($tg);

        return $this->chooseActionChatSender->sendActions($tg, $message);
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
        return $tg->button($this->provider->getLocaleComposeName($locale));
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

    public function getOtherLocaleButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $icon = $this->provider->getUnknownLocaleIcon();
        $name = $tg->trans('keyboard.other');

        return $tg->button($icon . ' ' . $name);
    }
}