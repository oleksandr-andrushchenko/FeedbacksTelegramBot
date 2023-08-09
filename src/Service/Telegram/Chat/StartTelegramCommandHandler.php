<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Enum\Telegram\TelegramView;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Intl\CountryProvider;
use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\Channel\FeedbackTelegramChannel;
use App\Service\Telegram\Conversation\ChooseCountryTelegramConversation;
use App\Service\Telegram\TelegramAwareHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StartTelegramCommandHandler
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SiteUrlGenerator $siteUrlGenerator,
        private readonly FeedbackUserSubscriptionManager $subscriptionManager,
    )
    {
    }

    public function handleStart(TelegramAwareHelper $tg): null
    {
        $this->describe($tg);

        $countries = $this->countryProvider->getCountries($tg->getLocaleCode());

        if (count($countries) === 1) {
            $country = array_values($countries)[0];

            $tg->getTelegram()->getMessengerUser()?->getUser()->setCountryCode($country->getCode());

            return $this->chooseActionChatSender->sendActions($tg);
        }

        return $tg->startConversation(ChooseCountryTelegramConversation::class)->null();
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
