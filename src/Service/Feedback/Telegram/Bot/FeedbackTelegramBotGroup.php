<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot;

use App\Entity\CommandOptions;
use App\Entity\Telegram\TelegramBotCommand;
use App\Entity\Telegram\ErrorTelegramBotCommand;
use App\Entity\Telegram\FallbackTelegramBotCommand;
use App\Entity\Telegram\TelegramBotPayment;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Feedback\Subscription\FeedbackSubscriptionPlanProvider;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\Bot\Chat\StartTelegramCommandHandler;
use App\Service\Feedback\Telegram\Bot\Chat\SubscriptionsTelegramChatSender;
use App\Service\Feedback\Telegram\Bot\Conversation\ContactTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\CountryTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\CreateFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\LocaleTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\LookupFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\PurgeConversationTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\RestartConversationTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\SearchFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\SubscribeTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\View\SubscriptionTelegramViewProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationFactory;
use App\Service\Telegram\Bot\Group\TelegramBotGroup;
use App\Service\Telegram\Bot\Group\TelegramBotGroupInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use Throwable;

class FeedbackTelegramBotGroup extends TelegramBotGroup implements TelegramBotGroupInterface
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
        TelegramBotAwareHelper $awareHelper,
        TelegramBotConversationFactory $conversationFactory,
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

    protected function getCommands(TelegramBotAwareHelper $tg): iterable
    {
        yield new TelegramBotCommand(self::START, fn () => $this->start($tg), menu: false);
        yield new TelegramBotCommand(self::CREATE, fn () => $this->create($tg), menu: true, key: 'create', beforeConversations: true);
        yield new TelegramBotCommand(self::SEARCH, fn () => $this->search($tg), menu: true, key: 'search', beforeConversations: true);
        yield new TelegramBotCommand(self::LOOKUP, fn () => $this->lookup($tg), menu: true, key: 'lookup', beforeConversations: true);
        yield new TelegramBotCommand(self::SUBSCRIBE, fn () => $this->subscribe($tg), menu: true, key: 'subscribe', beforeConversations: true);
        yield new TelegramBotCommand(self::SUBSCRIPTIONS, fn () => $this->subscriptions($tg), menu: true, key: 'subscriptions', beforeConversations: true);
        yield new TelegramBotCommand(self::COUNTRY, fn () => $this->country($tg), menu: true, key: 'country', beforeConversations: true);
        yield new TelegramBotCommand(self::LOCALE, fn () => $this->locale($tg), menu: true, key: 'locale', beforeConversations: true);
        yield new TelegramBotCommand(self::LIMITS, fn () => $this->limits($tg), menu: true, key: 'locale', beforeConversations: true);
        yield new TelegramBotCommand(self::PURGE, fn () => $this->purge($tg), menu: true, key: 'purge', beforeConversations: true);
        yield new TelegramBotCommand(self::CONTACT, fn () => $this->contact($tg), menu: true, key: 'contact', beforeConversations: true);
        yield new TelegramBotCommand(self::COMMANDS, fn () => $this->commands($tg), menu: true, key: 'commands', beforeConversations: true);
        yield new TelegramBotCommand(self::RESTART, fn () => $this->restart($tg), menu: true, key: 'restart', beforeConversations: true);

        yield new FallbackTelegramBotCommand(fn () => $this->fallback($tg));
        yield new ErrorTelegramBotCommand(fn (Throwable $exception) => $this->exception($tg));
    }

    public function fallback(TelegramBotAwareHelper $tg): null
    {
        return match ($tg->getInput()) {
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

    public function supportsUpdate(TelegramBotAwareHelper $tg): bool
    {
        $update = $tg->getBot()->getUpdate();

        return $update->getMessage()?->getChat()->getType() === 'private';
    }

    public function exception(TelegramBotAwareHelper $tg): null
    {
        $message = $tg->trans('reply.fail', [
            'contact_command' => sprintf('<u>%s</u>', $tg->command('contact', html: true)),
        ]);
        $message = $tg->failText($message);

        $tg->reply($message);

        $tg->stopCurrentConversation();

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function start(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        return $this->startHandler->handleStart($tg);
    }

    public function create(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(CreateFeedbackTelegramBotConversation::class)->null();
    }

    public function search(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(SearchFeedbackTelegramBotConversation::class)->null();
    }

    public function lookup(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(LookupFeedbackTelegramBotConversation::class)->null();
    }

    public function subscribe(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $messengerUser = $tg->getBot()->getMessengerUser();
        $activeSubscription = $this->subscriptionManager->getActiveSubscription($messengerUser);

        if ($activeSubscription === null) {
            return $tg->startConversation(SubscribeTelegramBotConversation::class)->null();
        }

        $message = $tg->trans('reply.subscribe.already_have');

        $tg->reply($message);

        $message = $this->subscriptionViewProvider->getSubscriptionTelegramView($tg, $activeSubscription);

        $tg->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function subscriptions(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $this->subscriptionsChatSender->sendFeedbackSubscriptions($tg);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function acceptPayment(TelegramBotPayment $payment, TelegramBotAwareHelper $tg): void
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

    public function country(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(CountryTelegramBotConversation::class)->null();
    }

    public function locale(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(LocaleTelegramBotConversation::class)->null();
    }

    public function limits(TelegramBotAwareHelper $tg): null
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

    public function purge(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(PurgeConversationTelegramBotConversation::class)->null();
    }

    public function contact(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(ContactTelegramBotConversation::class)->null();
    }

    public function commands(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $message = $tg->view('commands');

        $tg->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function restart(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(RestartConversationTelegramBotConversation::class)->null();
    }

    public function more(TelegramBotAwareHelper $tg): null
    {
        $tg->getBot()->getMessengerUser()->setShowExtendedKeyboard(true);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function less(TelegramBotAwareHelper $tg): null
    {
        $tg->getBot()->getMessengerUser()->setShowExtendedKeyboard(false);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function wrong(TelegramBotAwareHelper $tg): ?string
    {
        $message = $tg->trans('reply.wrong');
        $message = $tg->wrongText($message);
        $message .= "\n";

        return $this->chooseActionChatSender->sendActions($tg, text: $message, prependDefault: true);
    }
}