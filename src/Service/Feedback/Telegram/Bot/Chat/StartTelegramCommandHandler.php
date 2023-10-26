<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Chat;

use App\Enum\Site\SitePage;
use App\Service\Feedback\Telegram\Bot\Conversation\CountryTelegramBotConversation;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StartTelegramCommandHandler
{
    public function __construct(
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
        private readonly SiteUrlGenerator $siteUrlGenerator,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
    )
    {
    }

    public function handleStart(TelegramBotAwareHelper $tg): null
    {
        $this->reply($tg);

        if ($tg->getCountryCode() === null) {
            return $tg->startConversation(CountryTelegramBotConversation::class)->null();
        }

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function reply(TelegramBotAwareHelper $tg): void
    {
        $locale = $this->localeProvider->getLocale($tg->getLocaleCode());
        $parameters = [
            'username' => $tg->getBot()->getEntity()->getUsername(),
        ];
        $privacyPolicyLink = $this->siteUrlGenerator->generate(
            'app.telegram_site_page',
            $parameters + ['page' => SitePage::PRIVACY_POLICY->value],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $termsOfUseLink = $this->siteUrlGenerator->generate(
            'app.telegram_site_page',
            $parameters + ['page' => SitePage::TERMS_OF_USE->value],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $message = $tg->view('start', [
            'privacy_policy_link' => $privacyPolicyLink,
            'terms_of_use_link' => $termsOfUseLink,
            'country_icon' => $this->countryProvider->getCountryIconByCode($tg->getCountryCode()),
            'locale_icon' => $this->localeProvider->getLocaleIcon($locale),
        ]);

        $tg->reply($message);
    }
}
