<?php

declare(strict_types=1);

namespace App\Service\Telegram\Conversation;

use App\Entity\Telegram\TelegramConversationState;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Chat\FeedbackSubscriptionsTelegramChatSender;
use App\Service\Telegram\Chat\HintsTelegramChatSwitcher;
use App\Service\Telegram\TelegramAwareHelper;
use App\Entity\Telegram\TelegramConversation as Conversation;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseFeedbackActionTelegramConversation extends TelegramConversation implements TelegramConversationInterface
{
    public const STEP_ACTION_ASKED = 10;

    public function __construct(
        readonly TelegramAwareHelper $awareHelper,
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
        private readonly FeedbackSubscriptionsTelegramChatSender $subscriptionsChatSender,
        private readonly HintsTelegramChatSwitcher $hintsChatSwitcher,
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
            return $this->onActionAnswer($tg, $conversation);
        }

        return null;
    }

    public function askAction(TelegramAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_ACTION_ASKED);

        $keyboards = [
            $this->getCreateButton($tg),
            $this->getSearchButton($tg),
        ];

        if ($this->userSubscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser())) {
            $keyboards[] = $this->getSubscriptionsButton($tg);
        } elseif ($tg->getTelegram()->getOptions()->acceptPayments()) {
            $keyboards[] = $this->getPremiumButton($tg);
        }

        return $tg->reply($this->getActionAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public function onActionAnswer(TelegramAwareHelper $tg, Conversation $conversation): null
    {
        if ($tg->matchText(FeedbackTelegramChannel::CREATE_FEEDBACK) || $tg->matchText($this->getCreateButton($tg)->getText())) {
            return $tg->stopConversation($conversation)->startConversation(CreateFeedbackTelegramConversation::class)->null();
        }

        if ($tg->matchText(FeedbackTelegramChannel::SEARCH_FEEDBACK) || $tg->matchText($this->getSearchButton($tg)->getText())) {
            return $tg->stopConversation($conversation)->startConversation(SearchFeedbackTelegramConversation::class)->null();
        }

        if ($tg->matchText(FeedbackTelegramChannel::GET_PREMIUM) || $tg->matchText($this->getPremiumButton($tg)->getText())) {
            if ($this->userSubscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser())) {
                $this->subscriptionsChatSender->sendFeedbackSubscriptions($tg);

                return $this->askAction($tg);
            }

            if (!$tg->getTelegram()->getOptions()->acceptPayments()) {
                $tg->replyFail('reply.action.fail.not_accept_payments');

                return $this->askAction($tg);
            }

            return $tg->stopConversation($conversation)->startConversation(GetFeedbackPremiumTelegramConversation::class)->null();
        }

        if ($tg->matchText(FeedbackTelegramChannel::SUBSCRIPTIONS) || $tg->matchText($this->getSubscriptionsButton($tg)->getText())) {
            $this->subscriptionsChatSender->sendFeedbackSubscriptions($tg);

            return $this->askAction($tg);
        }

        if ($tg->matchText(FeedbackTelegramChannel::COUNTRY)) {
            return $tg->stopConversation($conversation)->startConversation(ChooseFeedbackCountryTelegramConversation::class)->null();
        }

        if ($tg->matchText(FeedbackTelegramChannel::PURGE)) {
            return $tg->stopConversation($conversation)->startConversation(PurgeAccountConversationTelegramConversation::class)->null();
        }

        if ($tg->matchText(FeedbackTelegramChannel::HINTS)) {
            $this->hintsChatSwitcher->toggleHints($tg);

            return $this->askAction($tg);
        }

        $tg->replyWrong();

        return $this->askAction($tg);
    }

    public static function getActionAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('ask.action.action');
    }

    public static function getCreateButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->transCommand('create'));
    }

    public static function getSearchButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->transCommand('search'));
    }

    public static function getPremiumButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->transCommand('premium'));
    }

    public static function getSubscriptionsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->transCommand('subscriptions'));
    }
}