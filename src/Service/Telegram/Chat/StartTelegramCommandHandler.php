<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Enum\Telegram\TelegramView;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\ChooseCountryTelegramConversation;
use App\Service\Telegram\TelegramAwareHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StartTelegramCommandHandler
{
    public function __construct(
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SiteUrlGenerator $siteUrlGenerator,
        private readonly FeedbackUserSubscriptionManager $subscriptionManager,
    )
    {
    }

    public function handleStart(TelegramAwareHelper $tg): null
    {
        $this->describe($tg);

        if ($tg->getCountryCode() === null) {
            return $tg->startConversation(ChooseCountryTelegramConversation::class)->null();
        }

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->isShowHints()) {
            return;
        }

        $commands = FeedbackTelegramChannel::COMMANDS;
        if (!$tg->getTelegram()->getBot()->acceptPayments()) {
            $commands = array_diff($commands, [
                FeedbackTelegramChannel::PREMIUM,
            ]);

            if (!$this->subscriptionManager->hasSubscription($tg->getTelegram()->getMessengerUser())) {
                $commands = array_diff($commands, [
                    FeedbackTelegramChannel::SUBSCRIPTIONS,
                ]);
            }
        }

        $tg->replyView(TelegramView::START, [
            'commands' => $commands,
            'privacy_policy_link' => $this->siteUrlGenerator->generate('app.site_privacy_policy', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            'terms_of_use_link' => $this->siteUrlGenerator->generate('app.site_terms_of_use', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
        ], disableWebPagePreview: true);
    }
}
