<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;
use App\Enum\Telegram\TelegramView;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Chat\StartTelegramCommandHandler;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use Longman\TelegramBot\Entities\KeyboardButton;

class RestartConversationTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CONFIRM_ASKED = 10;
    public const STEP_CONFIRMED = 20;
    public const STEP_CANCEL_PRESSED = 30;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly StartTelegramCommandHandler $startHandler,
    )
    {
        parent::__construct($awareHelper, new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            return $this->askConfirm($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.restart.canceled'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_CONFIRM_ASKED) {
            return $this->onConfirmAnswer($tg, $conversation);
        }

        return null;
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->isShowHints()) {
            return;
        }

        $tg->replyView(TelegramView::RESTART);
    }

    public function askConfirm(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_CONFIRM_ASKED);

        $keyboards = [];

        $keyboards[] = $this->getConfirmButton($tg);
        $keyboards[] = $this->getCancelButton($tg);

        return $tg->reply($this->getConfirmAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function onConfirmAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if (!$tg->matchText($this->getConfirmButton($tg)->getText())) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        $this->state->setStep(self::STEP_CONFIRMED);

        $tg->getTelegram()->getMessengerUser()?->setIsShowHints(true)?->setIsShowExtendedKeyboard(false);
        $tg->getTelegram()->getMessengerUser()?->getUser()->setCountryCode(null);

        $tg->stopConversation($conversation)->stopConversations();

        $tg->replyOk($tg->trans('reply.restart.ok'));

        return $this->startHandler->handleStart($tg);
    }

    public static function getConfirmAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('ask.restart.confirm');
    }

    public static function getConfirmButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.confirm'));
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }
}