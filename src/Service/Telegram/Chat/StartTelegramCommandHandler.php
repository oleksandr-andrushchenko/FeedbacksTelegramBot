<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\Conversation\CountryTelegramConversation;
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
        $this->describe($tg);

        if ($tg->getCountryCode() === null) {
            return $tg->startConversation(CountryTelegramConversation::class)->null();
        }

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function describe(TelegramAwareHelper $tg): void
    {
        if (!$tg->getTelegram()->getMessengerUser()->showHints()) {
            return;
        }

        $countryCode = $tg->getCountryCode();

        $tg->reply($tg->view('describe_start', [
            'privacy_policy_link' => $this->siteUrlGenerator->generate(
                'app.site_privacy_policy',
                [
                    '_locale' => $countryCode,
                ],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'terms_of_use_link' => $this->siteUrlGenerator->generate(
                'app.site_terms_of_use',
                [
                    '_locale' => $countryCode,
                ],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]));
    }
}
