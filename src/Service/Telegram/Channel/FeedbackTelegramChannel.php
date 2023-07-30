<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramPayment;
use App\Enum\Telegram\TelegramView;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Intl\CountryProvider;
use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Chat\PremiumDescribeTelegramChatSender;
use App\Service\Telegram\Chat\SubscriptionsTelegramChatSender;
use App\Service\Telegram\Chat\HintsTelegramChatSwitcher;
use App\Service\Telegram\Conversation\ChooseFeedbackCountryTelegramConversation;
use App\Service\Telegram\Conversation\GetFeedbackPremiumTelegramConversation;
use App\Service\Telegram\Conversation\CreateFeedbackTelegramConversation;
use App\Service\Telegram\Conversation\PurgeAccountConversationTelegramConversation;
use App\Service\Telegram\Conversation\SearchFeedbackTelegramConversation;
use App\Service\Telegram\FallbackTelegramCommand;
use App\Service\Telegram\TelegramCommand;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramConversationFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FeedbackTelegramChannel extends TelegramChannel implements TelegramChannelInterface
{
    public const START = '/start';
    public const CREATE_FEEDBACK = '/add';
    public const SEARCH_FEEDBACK = '/find';
    public const GET_PREMIUM = '/premium';
    public const SUBSCRIPTIONS = '/subscriptions';
    public const COUNTRY = '/country';
    public const HINTS = '/hints';
    public const PURGE = '/purge';
    public const RESTART = '/restart';

    public function __construct(
        TelegramAwareHelper $awareHelper,
        TelegramConversationFactory $conversationFactory,
        private readonly CountryProvider $countryProvider,
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
        private readonly SubscriptionsTelegramChatSender $subscriptionsChatSender,
        private readonly SiteUrlGenerator $siteUrlGenerator,
        private readonly HintsTelegramChatSwitcher $hintsChatSwitcher,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly PremiumDescribeTelegramChatSender $premiumDescribeChatSender,
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
        yield new TelegramCommand(self::CREATE_FEEDBACK, fn () => $this->create($tg), menu: true, key: 'create', beforeConversations: true);
        yield new TelegramCommand(self::SEARCH_FEEDBACK, fn () => $this->search($tg), menu: true, key: 'search', beforeConversations: true);

        if ($tg->getTelegram()->getOptions()->acceptPayments()) {
            yield new TelegramCommand(self::GET_PREMIUM, fn () => $this->premium($tg), menu: true, key: 'premium', beforeConversations: true);
        }

        yield new TelegramCommand(self::SUBSCRIPTIONS, fn () => $this->subscriptions($tg), menu: true, key: 'subscriptions', beforeConversations: true);
        yield new TelegramCommand(self::COUNTRY, fn () => $this->country($tg), menu: true, key: 'country', beforeConversations: true);
        yield new TelegramCommand(self::HINTS, fn () => $this->hints($tg), menu: true, key: 'hints', beforeConversations: true);
        yield new TelegramCommand(self::PURGE, fn () => $this->purge($tg), menu: true, key: 'purge', beforeConversations: true);
        yield new TelegramCommand(self::RESTART, fn () => $this->restart($tg), menu: true, key: 'restart', beforeConversations: true);

        // todo: "who've been looking for me" command
        // todo: "list my feedbacks" command
        // todo: "list feedbacks on me" command
        // todo: "subscribe on mine/somebodies feedbacks" command

        yield new FallbackTelegramCommand(fn () => $this->fallback($tg));
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

        $tg->replyWrong();

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function start(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations();

        $this->describeStart($tg);

        $countries = $this->countryProvider->getCountries($tg->getLanguageCode());

        if (count($countries) === 1) {
            $country = array_values($countries)[0];

            $tg->getTelegram()->getMessengerUser()?->getUser()->setCountryCode($country->getCode());

            return $this->chooseActionChatSender->sendActions($tg);
        }

        return $tg->startConversation(ChooseFeedbackCountryTelegramConversation::class)->null();
    }

    public function describeStart(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->isShowHints()) {
            return;
        }

        $commands = [];
        foreach ($this->getCommands($tg) as $command) {
            if (!$command instanceof TelegramCommand) {
                continue;
            }
            if ($command->getKey() !== null) {
                $commands[$command->getKey()] = $command->getName();
            }
        }

        $tg->replyView(TelegramView::START, [
            'commands' => $commands,
            'privacy_policy_link' => $this->siteUrlGenerator->generate('app.site_privacy_policy', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            'terms_of_use_link' => $this->siteUrlGenerator->generate('app.site_terms_of_use', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
        ], disableWebPagePreview: true);
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
            return $tg->startConversation(GetFeedbackPremiumTelegramConversation::class)->null();
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

        $tg->replyOk('reply.payment.ok', [
            'plan' => $tg->trans(sprintf('subscription_plan.%s', $userSubscription->getSubscriptionPlan()->name)),
            'expire_at' => $userSubscription->getExpireAt()->format($tg->trans('datetime_format')),
        ]);

        // todo: show buttons (or continue active conversation)

        $tg->stopConversations();

        $this->chooseActionChatSender->sendActions($tg);
    }

    public function country(TelegramAwareHelper $tg): null
    {
        return $tg->stopConversations()->startConversation(ChooseFeedbackCountryTelegramConversation::class)->null();
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

    public function restart(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations()->replyOk('reply.restart.ok');

        return $this->start($tg);
    }
}