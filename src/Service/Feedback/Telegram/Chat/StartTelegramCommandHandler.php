<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Chat;

use App\Service\Feedback\Telegram\Conversation\CountryTelegramConversation;
use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\TelegramAwareHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StartTelegramCommandHandler
{
    public function __construct(
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SiteUrlGenerator $siteUrlGenerator,
    )
    {
    }

    public function handleStart(TelegramAwareHelper $tg): null
    {
        $this->reply($tg);

        if ($tg->getCountryCode() === null) {
            return $tg->startConversation(CountryTelegramConversation::class)->null();
        }

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function reply(TelegramAwareHelper $tg): void
    {
        $countryCode = $tg->getCountryCode();
        $parameters = [
            '_locale' => $countryCode,
        ];
        $privacyPolicyLink = $this->siteUrlGenerator->generate('app.site_privacy_policy', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        $termsOfUseLink = $this->siteUrlGenerator->generate('app.site_terms_of_use', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        $message = $tg->view('start', [
            'privacy_policy_link' => $privacyPolicyLink,
            'terms_of_use_link' => $termsOfUseLink,
        ]);

        $tg->reply($message);
    }
}