<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Enum\Telegram\TelegramView;
use App\Exception\ValidatorException;
use App\Object\User\UserFeedbackMessageTransfer;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\User\UserFeedbackMessageCreator;
use Longman\TelegramBot\Entities\KeyboardButton;
use App\Entity\Telegram\TelegramConversation as Conversation;

class LeaveFeedbackMessageTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_MESSAGE_ASKED = 10;
    public const STEP_CANCEL_PRESSED = 20;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly UserFeedbackMessageCreator $messageCreator,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
    )
    {
        parent::__construct($awareHelper, new CreateFeedbackTelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            $this->describe($tg);

            return $this->askMessage($tg);
        }

        if ($tg->matchText(null)) {
            return $tg->replyWrong($tg->trans('reply.wrong'))->null();
        }

        if ($tg->matchText($this->getCancelButton($tg)->getText())) {
            $this->state->setStep(self::STEP_CANCEL_PRESSED);

            $tg->stopConversation($conversation)->replyUpset($tg->trans('reply.message.canceled'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        if ($this->state->getStep() === self::STEP_MESSAGE_ASKED) {
            return $this->onMessageAnswer($tg, $conversation);
        }

        return null;
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->isShowHints()) {
            return;
        }

        $tg->replyView(TelegramView::MESSAGE);
    }

    public function askMessage(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_MESSAGE_ASKED);

        return $tg->reply($tg->trans('ask.message.text'), $tg->keyboard($this->getCancelButton($tg)))->null();
    }

    public function onMessageAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        try {
            $this->messageCreator->createUserFeedbackMessage(
                new UserFeedbackMessageTransfer(
                    $conversation->getMessengerUser(),
                    $conversation->getMessengerUser()->getUser(),
                    $tg->getText()
                )
            );

            $tg->stopConversation($conversation)->replyOk($tg->trans('reply.message.ok'));

            return $this->chooseActionChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->askMessage($tg);
        }
    }

    public static function getCancelButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.cancel'));
    }
}