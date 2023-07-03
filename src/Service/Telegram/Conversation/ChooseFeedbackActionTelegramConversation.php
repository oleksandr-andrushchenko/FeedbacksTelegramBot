<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseFeedbackActionTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_ACTION_ASKED = 10;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
    )
    {
        parent::__construct($awareHelper, new TelegramConversationState());
    }

    public function invoke(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($this->state->getStep() === null) {
            return $this->askAction($tg);
        }

        if ($this->state->getStep() === self::STEP_ACTION_ASKED) {
            return $this->onConfirmAnswer($tg, $conversation);
        }

        return null;
    }

    public function askAction(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_ACTION_ASKED);

        return $tg->reply($this->getChooseActionAsk($tg), $tg->keyboard(
            static::getCreateButton($tg),
            static::getSearchButton($tg)
        ))->null();
    }

    public function onConfirmAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText(FeedbackTelegramChannel::CREATE_FEEDBACK) || $tg->matchText($this->getCreateButton($tg)->getText())) {
            return $tg->finishConversation($conversation)->startConversation(CreateFeedbackTelegramConversation::class)->null();
        }

        if ($tg->matchText(FeedbackTelegramChannel::SEARCH_FEEDBACK) || $tg->matchText($this->getSearchButton($tg)->getText())) {
            return $tg->finishConversation($conversation)->startConversation(SearchFeedbackTelegramConversation::class)->null();
        }

        $tg->replyWrong();

        return $this->askAction($tg);
    }

    public static function getChooseActionAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('feedbacks.ask.choose_action');
    }

    public static function getCreateButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('feedbacks.keyboard.choose_action.create');
    }

    public static function getSearchButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button('feedbacks.keyboard.choose_action.search');
    }
}