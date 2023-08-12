<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramPayment;
use App\Enum\Telegram\TelegramView;
use App\Exception\Telegram\TelegramException;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Chat\SubscribeDescribeTelegramChatSender;
use App\Service\Telegram\Chat\StartTelegramCommandHandler;
use App\Service\Telegram\Chat\SubscriptionsTelegramChatSender;
use App\Service\Telegram\Chat\HintsTelegramChatSwitcher;
use App\Service\Telegram\Conversation\CountryTelegramConversation;
use App\Service\Telegram\Conversation\LocaleTelegramConversation;
use App\Service\Telegram\Conversation\SubscribeTelegramConversation;
use App\Service\Telegram\Conversation\CreateFeedbackTelegramConversation;
use App\Service\Telegram\Conversation\ContactTelegramConversation;
use App\Service\Telegram\Conversation\PurgeConversationTelegramConversation;
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
    public const SUBSCRIBE = '/subscribe';
    public const SUBSCRIPTIONS = '/subscriptions';
    public const COUNTRY = '/country';
    public const LOCALE = '/language';
    public const HINTS = '/hints';
    public const PURGE = '/purge';
    public const CONTACT = '/contact';
    public const RESTART = '/restart';

    public const COMMANDS = [
        'create' => self::CREATE,
        'search' => self::SEARCH,
        'subscribe' => self::SUBSCRIBE,
        'subscriptions' => self::SUBSCRIPTIONS,
        'country' => self::COUNTRY,
        'locale' => self::LOCALE,
        'hints' => self::HINTS,
        'purge' => self::PURGE,
        'contact' => self::CONTACT,
        'restart' => self::RESTART,
    ];

    public function __construct(
        TelegramAwareHelper $awareHelper,
        TelegramConversationFactory $conversationFactory,
        private readonly FeedbackUserSubscriptionManager $subscriptionManager,
        private readonly SubscriptionsTelegramChatSender $subscriptionsChatSender,
        private readonly HintsTelegramChatSwitcher $hintsChatSwitcher,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SubscribeDescribeTelegramChatSender $subscribeDescribeChatSender,
        private readonly StartTelegramCommandHandler $startHandler,
    )
    {
        parent::__construct($awareHelper, $conversationFactory);
    }

    protected function getCommands(TelegramAwareHelper $tg): iterable
    {
        yield new TelegramCommand(self::START, fn () => $this->start($tg), menu: false);
        yield new TelegramCommand(self::CREATE, fn () => $this->create($tg), menu: true, key: 'create', beforeConversations: true);
        yield new TelegramCommand(self::SEARCH, fn () => $this->search($tg), menu: true, key: 'search', beforeConversations: true);
        yield new TelegramCommand(self::SUBSCRIBE, fn () => $this->subscribe($tg), menu: true, key: 'subscribe', beforeConversations: true);
        yield new TelegramCommand(self::SUBSCRIPTIONS, fn () => $this->subscriptions($tg), menu: true, key: 'subscriptions', beforeConversations: true);
        yield new TelegramCommand(self::COUNTRY, fn () => $this->country($tg), menu: true, key: 'country', beforeConversations: true);
        yield new TelegramCommand(self::LOCALE, fn () => $this->locale($tg), menu: true, key: 'locale', beforeConversations: true);
        yield new TelegramCommand(self::HINTS, fn () => $this->hints($tg), menu: true, key: 'hints', beforeConversations: true);
        yield new TelegramCommand(self::PURGE, fn () => $this->purge($tg), menu: true, key: 'purge', beforeConversations: true);
        yield new TelegramCommand(self::CONTACT, fn () => $this->contact($tg), menu: true, key: 'contact', beforeConversations: true);
        yield new TelegramCommand(self::RESTART, fn () => $this->restart($tg), menu: true, key: 'restart', beforeConversations: true);

        yield new FallbackTelegramCommand(fn () => $this->fallback($tg));
        yield new ErrorTelegramCommand(fn (TelegramException $exception) => $this->exception($tg));
    }

    public function fallback(TelegramAwareHelper $tg): null
    {
        return match ($tg->getText()) {
            $this->chooseActionChatSender->getCreateButton($tg)->getText() => $this->create($tg),
            $this->chooseActionChatSender->getSearchButton($tg)->getText() => $this->search($tg),
            $this->chooseActionChatSender->getSubscribeButton($tg)->getText() => $this->subscribe($tg),
            $this->chooseActionChatSender->getSubscriptionsButton($tg)->getText() => $this->subscriptions($tg),
            $this->chooseActionChatSender->getCountryButton($tg)->getText() => $this->country($tg),
            $this->chooseActionChatSender->getLocaleButton($tg)->getText() => $this->locale($tg),
            $this->chooseActionChatSender->getHintsButton($tg)->getText() => $this->hints($tg),
            $this->chooseActionChatSender->getPurgeButton($tg)->getText() => $this->purge($tg),
            $this->chooseActionChatSender->getContactButton($tg)->getText() => $this->contact($tg),
            $this->chooseActionChatSender->getRestartButton($tg)->getText() => $this->restart($tg),
            $this->chooseActionChatSender->getShowLessButton($tg)->getText() => $this->less($tg),
            $this->chooseActionChatSender->getShowMoreButton($tg)->getText() => $this->more($tg),
            default => $this->wrong($tg),
        };
    }

    public function exception(TelegramAwareHelper $tg): null
    {
        $tg->replyFail($tg->trans('reply.fail.unknown'));

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

    public function subscribe(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations();

        if (!$tg->getTelegram()->getBot()->acceptPayments()) {
            $tg->replyFail($tg->trans('reply.fail.not_accept_payments'));

            return $this->chooseActionChatSender->sendActions($tg);
        }

        $messengerUser = $tg->getTelegram()->getMessengerUser();
        $activeSubscription = $this->subscriptionManager->getActiveSubscription($messengerUser);

        if ($activeSubscription === null) {
            return $tg->startConversation(SubscribeTelegramConversation::class)->null();
        }

        $this->describeSubscribe($tg);

        $tg->reply($tg->trans('reply.subscribe.already_have'))
            ->reply($tg->view(TelegramView::SUBSCRIPTION, [
                'subscription' => $activeSubscription,
            ]), parseMode: 'HTML')->null()
        ;

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function subscriptions(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations();

        $this->describeSubscribe($tg);

        $this->subscriptionsChatSender->sendFeedbackSubscriptions($tg);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function describeSubscribe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $this->subscribeDescribeChatSender->sendSubscribeDescribe($tg);
    }

    public function acceptPayment(TelegramPayment $payment, TelegramAwareHelper $tg): void
    {
        $subscription = $this->subscriptionManager->createByTelegramPayment($payment);

        $tg->replyOk($tg->trans('reply.payment.ok', [
            'plan' => $tg->trans(sprintf('subscription_plan.%s', $subscription->getSubscriptionPlan()->name)),
            'expire_at' => $subscription->getExpireAt()->format($tg->trans('datetime_format')),
        ], domain: 'tg.subscribe'));

        // todo: show buttons (or continue active conversation)

        $tg->stopConversations();

        $this->chooseActionChatSender->sendActions($tg);
    }

    public function country(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(CountryTelegramConversation::class)->null();
    }

    public function locale(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(LocaleTelegramConversation::class)->null();
    }

    public function hints(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations();

        $this->hintsChatSwitcher->toggleHints($tg);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function purge(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(PurgeConversationTelegramConversation::class)->null();
    }

    public function contact(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(ContactTelegramConversation::class)->null();
    }

    public function restart(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(RestartConversationTelegramConversation::class)->null();
    }

    public function more(TelegramAwareHelper $tg): null
    {
        $tg->getTelegram()->getMessengerUser()->setIsShowExtendedKeyboard(true);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function less(TelegramAwareHelper $tg): null
    {
        $tg->getTelegram()->getMessengerUser()->setIsShowExtendedKeyboard(false);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function wrong(TelegramAwareHelper $tg): null|string
    {
        $tg->replyWrong($tg->trans('reply.wrong'));

        return $this->chooseActionChatSender->sendActions($tg);
    }
}