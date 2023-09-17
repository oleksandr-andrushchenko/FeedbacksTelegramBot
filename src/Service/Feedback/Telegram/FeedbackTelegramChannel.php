<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram;

use App\Entity\CommandOptions;
use App\Entity\Telegram\TelegramPayment;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Feedback\Subscription\FeedbackSubscriptionPlanProvider;
use App\Service\Feedback\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\Chat\StartTelegramCommandHandler;
use App\Service\Feedback\Telegram\Chat\SubscriptionsTelegramChatSender;
use App\Service\Feedback\Telegram\Conversation\ContactTelegramConversation;
use App\Service\Feedback\Telegram\Conversation\CountryTelegramConversation;
use App\Service\Feedback\Telegram\Conversation\CreateFeedbackTelegramConversation;
use App\Service\Feedback\Telegram\Conversation\LocaleTelegramConversation;
use App\Service\Feedback\Telegram\Conversation\LookupTelegramConversation;
use App\Service\Feedback\Telegram\Conversation\PurgeConversationTelegramConversation;
use App\Service\Feedback\Telegram\Conversation\RestartConversationTelegramConversation;
use App\Service\Feedback\Telegram\Conversation\SearchFeedbackTelegramConversation;
use App\Service\Feedback\Telegram\Conversation\SubscribeTelegramConversation;
use App\Service\Feedback\Telegram\View\SubscriptionTelegramViewProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\Channel\TelegramChannel;
use App\Service\Telegram\Channel\TelegramChannelInterface;
use App\Service\Telegram\Command\TelegramErrorCommand;
use App\Service\Telegram\Command\TelegramFallbackCommand;
use App\Service\Telegram\Command\TelegramCommand;
use App\Service\Telegram\Conversation\TelegramConversationFactory;
use App\Service\Telegram\TelegramAwareHelper;
use Throwable;

class FeedbackTelegramChannel extends TelegramChannel implements TelegramChannelInterface
{
    public const START = '/start';
    public const CREATE = '/add';
    public const SEARCH = '/find';
    public const LOOKUP = '/lookup';
    public const SUBSCRIBE = '/subscribe';
    public const SUBSCRIPTIONS = '/subscriptions';
    public const COUNTRY = '/country';
    public const LOCALE = '/locale';
    public const LIMITS = '/limits';
    public const PURGE = '/purge';
    public const CONTACT = '/contact';
    public const COMMANDS = '/commands';
    public const RESTART = '/restart';

    public const SUPPORTS = [
        'create' => self::CREATE,
        'search' => self::SEARCH,
        'lookup' => self::LOOKUP,
        'subscribe' => self::SUBSCRIBE,
        'subscriptions' => self::SUBSCRIPTIONS,
        'country' => self::COUNTRY,
        'locale' => self::LOCALE,
        'limits' => self::LIMITS,
        'purge' => self::PURGE,
        'contact' => self::CONTACT,
        'commands' => self::COMMANDS,
        'restart' => self::RESTART,
    ];

    public function __construct(
        TelegramAwareHelper $awareHelper,
        TelegramConversationFactory $conversationFactory,
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly SubscriptionsTelegramChatSender $subscriptionsChatSender,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly StartTelegramCommandHandler $startHandler,
        private readonly SubscriptionTelegramViewProvider $subscriptionViewProvider,
        private readonly TimeProvider $timeProvider,
        private readonly FeedbackSubscriptionPlanProvider $subscriptionPlanProvider,
        private readonly CommandOptions $createOptions,
        private readonly CommandOptions $searchOptions,
        private readonly CommandOptions $lookupOptions,
    )
    {
        parent::__construct($awareHelper, $conversationFactory);
    }

    protected function getCommands(TelegramAwareHelper $tg): iterable
    {
        yield new TelegramCommand(self::START, fn () => $this->start($tg), menu: false);
        yield new TelegramCommand(self::CREATE, fn () => $this->create($tg), menu: true, key: 'create', beforeConversations: true);
        yield new TelegramCommand(self::SEARCH, fn () => $this->search($tg), menu: true, key: 'search', beforeConversations: true);
        yield new TelegramCommand(self::LOOKUP, fn () => $this->lookup($tg), menu: true, key: 'lookup', beforeConversations: true);
        yield new TelegramCommand(self::SUBSCRIBE, fn () => $this->subscribe($tg), menu: true, key: 'subscribe', beforeConversations: true);
        yield new TelegramCommand(self::SUBSCRIPTIONS, fn () => $this->subscriptions($tg), menu: true, key: 'subscriptions', beforeConversations: true);
        yield new TelegramCommand(self::COUNTRY, fn () => $this->country($tg), menu: true, key: 'country', beforeConversations: true);
        yield new TelegramCommand(self::LOCALE, fn () => $this->locale($tg), menu: true, key: 'locale', beforeConversations: true);
        yield new TelegramCommand(self::LIMITS, fn () => $this->limits($tg), menu: true, key: 'locale', beforeConversations: true);
        yield new TelegramCommand(self::PURGE, fn () => $this->purge($tg), menu: true, key: 'purge', beforeConversations: true);
        yield new TelegramCommand(self::CONTACT, fn () => $this->contact($tg), menu: true, key: 'contact', beforeConversations: true);
        yield new TelegramCommand(self::COMMANDS, fn () => $this->commands($tg), menu: true, key: 'commands', beforeConversations: true);
        yield new TelegramCommand(self::RESTART, fn () => $this->restart($tg), menu: true, key: 'restart', beforeConversations: true);

        yield new TelegramFallbackCommand(fn () => $this->fallback($tg));
        yield new TelegramErrorCommand(fn (Throwable $exception) => $this->exception($tg));
    }

    public function fallback(TelegramAwareHelper $tg): null
    {
        return match ($tg->getText()) {
            $this->chooseActionChatSender->getCreateButton($tg)->getText() => $this->create($tg),
            $this->chooseActionChatSender->getSearchButton($tg)->getText() => $this->search($tg),
            $this->chooseActionChatSender->getLookupButton($tg)->getText() => $this->lookup($tg),
            $this->chooseActionChatSender->getSubscribeButton($tg)->getText() => $this->subscribe($tg),
            $this->chooseActionChatSender->getSubscriptionsButton($tg)->getText() => $this->subscriptions($tg),
            $this->chooseActionChatSender->getCountryButton($tg)->getText() => $this->country($tg),
            $this->chooseActionChatSender->getLocaleButton($tg)->getText() => $this->locale($tg),
            $this->chooseActionChatSender->getLimitsButton($tg)->getText() => $this->limits($tg),
            $this->chooseActionChatSender->getPurgeButton($tg)->getText() => $this->purge($tg),
            $this->chooseActionChatSender->getContactButton($tg)->getText() => $this->contact($tg),
            $this->chooseActionChatSender->getCommandsButton($tg)->getText() => $this->commands($tg),
            $this->chooseActionChatSender->getRestartButton($tg)->getText() => $this->restart($tg),
            $this->chooseActionChatSender->getShowLessButton($tg)->getText() => $this->less($tg),
            $this->chooseActionChatSender->getShowMoreButton($tg)->getText() => $this->more($tg),
            default => $this->wrong($tg)
        };
    }

    public function supportsUpdate(TelegramAwareHelper $tg): bool
    {
        $update = $tg->getTelegram()->getUpdate();

        return $update->getMessage()?->getChat()->getType() === 'private';
    }

    public function exception(TelegramAwareHelper $tg): null
    {
        $message = $tg->trans('reply.fail', [
            'contact_command' => sprintf('<u>%s</u>', $tg->command('contact', html: true)),
        ]);
        $message = $tg->failText($message);

        $tg->reply($message);

        $tg->stopCurrentConversation();

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function start(TelegramAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        return $this->startHandler->handleStart($tg);
    }

    public function create(TelegramAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(CreateFeedbackTelegramConversation::class)->null();
    }

    public function search(TelegramAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(SearchFeedbackTelegramConversation::class)->null();
    }

    public function lookup(TelegramAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(LookupTelegramConversation::class)->null();
    }

    public function subscribe(TelegramAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        if (!$tg->getTelegram()->getBot()->acceptPayments()) {
            $parameters = [
                'contact_command' => $tg->command('contact', html: true),
            ];
            $message = $tg->trans('reply.not_accept_payments', parameters: $parameters);
            $message = $tg->failText($message);

            return $this->chooseActionChatSender->sendActions($tg, $message);
        }

        $messengerUser = $tg->getTelegram()->getMessengerUser();
        $activeSubscription = $this->subscriptionManager->getActiveSubscription($messengerUser);

        if ($activeSubscription === null) {
            return $tg->startConversation(SubscribeTelegramConversation::class)->null();
        }

        $message = $tg->trans('reply.subscribe.already_have');

        $tg->reply($message);

        $message = $this->subscriptionViewProvider->getSubscriptionTelegramView($tg, $activeSubscription);

        $tg->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function subscriptions(TelegramAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $this->subscriptionsChatSender->sendFeedbackSubscriptions($tg);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function acceptPayment(TelegramPayment $payment, TelegramAwareHelper $tg): void
    {
        $subscription = $this->subscriptionManager->createByTelegramPayment($payment);

        $plan = $this->subscriptionPlanProvider->getSubscriptionPlanName($subscription->getSubscriptionPlan());
        $expireAt = $this->timeProvider->getDatetime($subscription->getExpireAt());
        $parameters = [
            'plan' => $plan,
            'expire_at' => $expireAt,
        ];
        $message = $tg->trans('reply.payment_ok', $parameters, domain: 'subscribe');
        $message = $tg->okText($message);

        $tg->reply($message);

        $tg->stopCurrentConversation();

        $this->chooseActionChatSender->sendActions($tg);
    }

    public function country(TelegramAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(CountryTelegramConversation::class)->null();
    }

    public function locale(TelegramAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(LocaleTelegramConversation::class)->null();
    }

    public function limits(TelegramAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $message = $tg->view('limits', [
            'commands' => [
                'create' => $this->createOptions->getLimits(),
                'search' => $this->searchOptions->getLimits(),
                'lookup' => $this->lookupOptions->getLimits(),
            ],
        ]);

        $tg->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function purge(TelegramAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(PurgeConversationTelegramConversation::class)->null();
    }

    public function contact(TelegramAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(ContactTelegramConversation::class)->null();
    }

    public function commands(TelegramAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $message = $tg->view('commands');

        $tg->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function restart(TelegramAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(RestartConversationTelegramConversation::class)->null();
    }

    public function more(TelegramAwareHelper $tg): null
    {
        $tg->getTelegram()->getMessengerUser()->setShowExtendedKeyboard(true);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function less(TelegramAwareHelper $tg): null
    {
        $tg->getTelegram()->getMessengerUser()->setShowExtendedKeyboard(false);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function wrong(TelegramAwareHelper $tg): ?string
    {
        $message = $tg->trans('reply.wrong');
        $message = $tg->wrongText($message);

        $tg->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }
}