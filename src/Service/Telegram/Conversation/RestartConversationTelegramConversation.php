<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Chat\StartTelegramCommandHandler;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use Longman\TelegramBot\Entities\KeyboardButton;

class RestartConversationTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CONFIRM_QUERIED = 10;
    public const STEP_CONFIRMED = 20;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly StartTelegramCommandHandler $startHandler,
        private readonly CountryProvider $countryProvider,
    )
    {
        parent::__construct($awareHelper, new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $conversation),
        };
    }

    public function start(TelegramAwareHelper $tg): ?string
    {
        $this->describe($tg);

        return $this->queryConfirm($tg);
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $tg->reply($tg->view('describe_restart'));
    }

    public function queryConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $buttons = [];

        $buttons[] = $this->getConfirmYesButton($tg);
        $buttons[] = $this->getConfirmNoButton($tg);

        return $tg->reply($this->getConfirmQuery($tg), $tg->keyboard(...$buttons))->null();
    }

    public function gotConfirm(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText($this->getConfirmNoButton($tg)->getText())) {
            return $this->chooseActionChatSender->sendActions($tg->stopConversation($conversation));
        }
        if (!$tg->matchText($this->getConfirmYesButton($tg)->getText())) {
            return $this->queryConfirm($tg->replyWrong($tg->trans('reply.wrong')));
        }

        $this->state->setStep(self::STEP_CONFIRMED);

        $countryCode = $tg->getTelegram()->getBot()->getCountryCode();
        $country = $this->countryProvider->getCountry($countryCode);

        $tg->getTelegram()->getMessengerUser()
            ?->setIsShowHints(true)
            ?->setIsShowExtendedKeyboard(false)
            ?->setLocaleCode($country->getLocaleCodes()[0] ?? null)
            ?->getUser()
            ?->setCountryCode($country->getCode())
            ?->setLocaleCode($country->getLocaleCodes()[0] ?? null)
            ?->setCurrencyCode($country->getCurrencyCode())
            ?->setTimezone($country->getTimezones()[0] ?? null)
        ;

        $tg->stopConversation($conversation)->stopConversations();

        $tg->replyOk($tg->trans('reply.ok', domain: 'tg.restart'));

        return $this->startHandler->handleStart($tg);
    }

    public static function getConfirmQuery(TelegramAwareHelper $tg): string
    {
        return $tg->trans('query.confirm', domain: 'tg.restart');
    }

    public static function getConfirmYesButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.yes'));
    }

    public static function getConfirmNoButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.no'));
    }
}