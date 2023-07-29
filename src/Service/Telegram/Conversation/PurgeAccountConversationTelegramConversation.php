<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;
use App\Enum\Telegram\TelegramView;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use App\Service\User\UserDataPurger;
use Longman\TelegramBot\Entities\KeyboardButton;

class PurgeAccountConversationTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_CONFIRM_ASKED = 10;
    public const STEP_CONFIRMED = 20;
    public const STEP_CANCEL_PRESSED = 30;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly UserDataPurger $userDataPurger,
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
            return $tg->replyWrong()->null();
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            return $tg->stopConversation($conversation)
                ->replyUpset('reply.purge.canceled')
                ->startConversation(ChooseFeedbackActionTelegramConversation::class)
                ->null()
            ;
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

        $tg->replyView(TelegramView::PURGE, [
            'items' => [
                'username',
                'name',
                'language',
                'country',
                'phone_number',
                'email',
            ],
        ]);
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
            return $tg->replyWrong()->null();
        }

        $this->state->setStep(self::STEP_CONFIRMED);

        $this->userDataPurger->purgeUserData($tg->getTelegram()->getMessengerUser()->getUser());

        $tg->replyOk('reply.purge.ok',)->stopConversation($conversation);

        return null;
    }

    public static function getConfirmAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('ask.purge.confirm');
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