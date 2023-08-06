<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramPayment;
use App\Enum\Telegram\TelegramView;
use App\Exception\Telegram\TelegramException;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Chat\PremiumDescribeTelegramChatSender;
use App\Service\Telegram\Chat\StartTelegramCommandHandler;
use App\Service\Telegram\Chat\SubscriptionsTelegramChatSender;
use App\Service\Telegram\Chat\HintsTelegramChatSwitcher;
use App\Service\Telegram\Conversation\ChooseCountryTelegramConversation;
use App\Service\Telegram\Conversation\ChooseLocaleTelegramConversation;
use App\Service\Telegram\Conversation\GetPremiumTelegramConversation;
use App\Service\Telegram\Conversation\CreateFeedbackTelegramConversation;
use App\Service\Telegram\Conversation\LeaveFeedbackMessageTelegramConversation;
use App\Service\Telegram\Conversation\PurgeAccountConversationTelegramConversation;
use App\Service\Telegram\Conversation\RestartConversationTelegramConversation;
use App\Service\Telegram\Conversation\SearchFeedbackTelegramConversation;
use App\Service\Telegram\ErrorTelegramCommand;
use App\Service\Telegram\FallbackTelegramCommand;
use App\Service\Telegram\TelegramCommand;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramConversationFactory;

class FeedbackTelegramChannel extends TelegramChannel implements TelegramChannelInterface
{
    public const START = '/start';
    public const CREATE = '/add';
    public const SEARCH = '/find';
    public const PREMIUM = '/premium';
    public const SUBSCRIPTIONS = '/subscriptions';
    public const COUNTRY = '/country';
    public const LOCALE = '/language';
    public const HINTS = '/hints';
    public const PURGE = '/purge';
    public const MESSAGE = '/message';
    public const RESTART = '/restart';

    public const COMMANDS = [
        'create' => self::CREATE,
        'search' => self::SEARCH,
        'premium' => self::PREMIUM,
        'subscriptions' => self::SUBSCRIPTIONS,
        'country' => self::COUNTRY,
        'locale' => self::LOCALE,
        'hints' => self::HINTS,
        'purge' => self::PURGE,
        'message' => self::MESSAGE,
        'restart' => self::RESTART,
    ];

    public function __construct(
        TelegramAwareHelper $awareHelper,
        TelegramConversationFactory $conversationFactory,
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
        private readonly SubscriptionsTelegramChatSender $subscriptionsChatSender,
        private readonly HintsTelegramChatSwitcher $hintsChatSwitcher,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly PremiumDescribeTelegramChatSender $premiumDescribeChatSender,
        private readonly StartTelegramCommandHandler $startHandler,
    )
    {
        parent::__construct($awareHelper, $conversationFactory);
    }

    /**
     * @param TelegramAwareHelper $tg
     * @return iterable
     */
    protected function getCommands(TelegramAwareHelper $tg): iterable
    {
        yield new TelegramCommand(self::START, fn () => $this->start($tg), menu: false);
        yield new TelegramCommand(self::CREATE, fn () => $this->create($tg), menu: true, key: 'create', beforeConversations: true);
        yield new TelegramCommand(self::SEARCH, fn () => $this->search($tg), menu: true, key: 'search', beforeConversations: true);

        if ($tg->getTelegram()->getOptions()->acceptPayments()) {
            yield new TelegramCommand(self::PREMIUM, fn () => $this->premium($tg), menu: true, key: 'premium', beforeConversations: true);
        }

        yield new TelegramCommand(self::SUBSCRIPTIONS, fn () => $this->subscriptions($tg), menu: true, key: 'subscriptions', beforeConversations: true);
        yield new TelegramCommand(self::COUNTRY, fn () => $this->country($tg), menu: true, key: 'country', beforeConversations: true);

        if ($tg->getCountryCode() !== null) {
            yield new TelegramCommand(self::LOCALE, fn () => $this->locale($tg), menu: true, key: 'locale', beforeConversations: true);
        }

        yield new TelegramCommand(self::HINTS, fn () => $this->hints($tg), menu: true, key: 'hints', beforeConversations: true);
        yield new TelegramCommand(self::PURGE, fn () => $this->purge($tg), menu: true, key: 'purge', beforeConversations: true);
        yield new TelegramCommand(self::MESSAGE, fn () => $this->message($tg), menu: true, key: 'message', beforeConversations: true);
        yield new TelegramCommand(self::RESTART, fn () => $this->restart($tg), menu: true, key: 'restart', beforeConversations: true);

        // todo: "who've been looking for me" command
        // todo: "list my feedbacks" command
        // todo: "list feedbacks on me" command
        // todo: "subscribe on mine/somebodies feedbacks" command
        // todo: after country selection - link to che channel
        // todo: add site links (to bot)
        // todo: add country flag to feedback view
        // todo: add command: how many times user X were been searched for (top command, usually - it gonna be current account - search for itself, but how many times somebody were searching me)
        // todo: manual payments
        // todo: ban users
        // todo: add check payment possibility (does at least payment method exists), + implement manual payments

        yield new FallbackTelegramCommand(fn () => $this->fallback($tg));
        yield new ErrorTelegramCommand(fn (TelegramException $exception) => $this->exception($tg));
    }

    public function fallback(TelegramAwareHelper $tg): null
    {
        if ($tg->matchText($this->chooseActionChatSender->getCreateButton($tg)->getText())) {
            return $this->create($tg);
        }
        if ($tg->matchText($this->chooseActionChatSender->getSearchButton($tg)->getText())) {
            return $this->search($tg);
        }
        if ($tg->matchText($this->chooseActionChatSender->getPremiumButton($tg)->getText())) {
            return $this->premium($tg);
        }
        if ($tg->matchText($this->chooseActionChatSender->getSubscriptionsButton($tg)->getText())) {
            return $this->subscriptions($tg);
        }

        if ($tg->getTelegram()->getMessengerUser()->isShowExtendedKeyboard()) {
            if ($tg->matchText($this->chooseActionChatSender->getCountryButton($tg)->getText())) {
                return $this->country($tg);
            }
            if ($tg->getCountryCode() !== null && $tg->matchText($this->chooseActionChatSender->getLocaleButton($tg)->getText())) {
                return $this->locale($tg);
            }
            if ($tg->matchText($this->chooseActionChatSender->getHintsButton($tg)->getText())) {
                return $this->hints($tg);
            }
            if ($tg->matchText($this->chooseActionChatSender->getPurgeButton($tg)->getText())) {
                return $this->purge($tg);
            }
            if ($tg->matchText($this->chooseActionChatSender->getMessageButton($tg)->getText())) {
                return $this->message($tg);
            }
            if ($tg->matchText($this->chooseActionChatSender->getRestartButton($tg)->getText())) {
                return $this->restart($tg);
            }
            if ($tg->matchText($this->chooseActionChatSender->getShowLessButton($tg)->getText())) {
                $tg->getTelegram()->getMessengerUser()->setIsShowExtendedKeyboard(false);

                return $this->chooseActionChatSender->sendActions($tg);
            }
        } else {
            if ($tg->matchText($this->chooseActionChatSender->getShowMoreButton($tg)->getText())) {
                $tg->getTelegram()->getMessengerUser()->setIsShowExtendedKeyboard(true);

                return $this->chooseActionChatSender->sendActions($tg);
            }
        }

        $tg->replyWrong($tg->trans('reply.wrong'));

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function exception(TelegramAwareHelper $tg): null
    {
        $tg->replyFail($tg->trans('reply.fail'));

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function start(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations();

        return $this->startHandler->handleStart($tg);
    }

    public function create(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(CreateFeedbackTelegramConversation::class)->null();
    }

    public function search(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(SearchFeedbackTelegramConversation::class)->null();
    }

    public function premium(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations();

        $activeSubscription = $this->userSubscriptionManager->getActiveSubscription($tg->getTelegram()->getMessengerUser());

        if ($activeSubscription === null) {
            return $tg->startConversation(GetPremiumTelegramConversation::class)->null();
        }

        $this->describePremium($tg);

        $tg->reply($tg->trans('reply.premium.already_have'))
            ->replyView(TelegramView::SUBSCRIPTION, [
                'subscription' => $activeSubscription,
            ])->null()
        ;

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function subscriptions(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations();

        $this->describePremium($tg);

        $this->subscriptionsChatSender->sendFeedbackSubscriptions($tg);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function describePremium(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->isShowHints()) {
            return;
        }

        $this->premiumDescribeChatSender->sendPremiumDescribe($tg);
    }

    public function acceptPayment(TelegramPayment $payment, TelegramAwareHelper $tg): void
    {
        $userSubscription = $this->userSubscriptionManager->createByTelegramPayment($payment);

        $tg->replyOk($tg->trans('reply.payment.ok', [
            'plan' => $tg->trans(sprintf('subscription_plan.%s', $userSubscription->getSubscriptionPlan()->name)),
            'expire_at' => $userSubscription->getExpireAt()->format($tg->trans('datetime_format')),
        ]));

        // todo: show buttons (or continue active conversation)

        $tg->stopConversations();

        $this->chooseActionChatSender->sendActions($tg);
    }

    public function country(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(ChooseCountryTelegramConversation::class)->null();
    }

    public function locale(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(ChooseLocaleTelegramConversation::class)->null();
    }

    public function hints(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations();

        $this->hintsChatSwitcher->toggleHints($tg);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function purge(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(PurgeAccountConversationTelegramConversation::class)->null();
    }

    public function message(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(LeaveFeedbackMessageTelegramConversation::class)->null();
    }

    public function restart(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(RestartConversationTelegramConversation::class)->null();
    }
}