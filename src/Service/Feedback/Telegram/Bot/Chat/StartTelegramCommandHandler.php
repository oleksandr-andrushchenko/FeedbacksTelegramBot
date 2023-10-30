<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Chat;

use App\Enum\Site\SitePage;
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

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function reply(TelegramBotAwareHelper $tg): void
    {
        $domain = 'start';
        $locale = $this->localeProvider->getLocale($tg->getLocaleCode());
        $message = $tg->infoText($tg->trans('title', domain: $domain));
//        $message .= "\n\n";
//        $parameters = [
//            'username' => $tg->getBot()->getEntity()->getUsername(),
//        ];
//        $privacyPolicyLink = $this->siteUrlGenerator->generate(
//            'app.telegram_site_page',
//            $parameters + ['page' => SitePage::PRIVACY_POLICY->value],
//            UrlGeneratorInterface::ABSOLUTE_URL
//        );
//        $privacyPolicyAnchor = $tg->trans('privacy_policy', domain: $domain);
//        $parameters['privacy_policy'] = sprintf('<a href="%s">%s</a>', $privacyPolicyLink, $privacyPolicyAnchor);
//        $termsOfUseLink = $this->siteUrlGenerator->generate(
//            'app.telegram_site_page',
//            $parameters + ['page' => SitePage::TERMS_OF_USE->value],
//            UrlGeneratorInterface::ABSOLUTE_URL
//        );
//        $termsOfUseAnchor = $tg->trans('terms_of_use', domain: $domain);
//        $parameters['terms_of_use'] = sprintf('<a href="%s">%s</a>', $termsOfUseLink, $termsOfUseAnchor);
//        $message .= $tg->attentionText($tg->trans('agreements', parameters: $parameters, domain: $domain));
        $message .= "\n\n";
        $parameters = [];
        $parameters['country_command'] = $tg->command('country', icon: $this->countryProvider->getCountryIconByCode($tg->getCountryCode()), html: true);
        $message .= $tg->infoText($tg->trans('country', parameters: $parameters, domain: $domain));
        $message .= "\n";
        $parameters = [];
        $parameters['locale_command'] = $tg->command('locale', icon: $this->localeProvider->getLocaleIcon($locale), html: true);
        $message .= $tg->trans('locale', parameters: $parameters, domain: $domain);
        $message .= "\n";
        $parameters = [];
        $parameters['commands_command'] = $tg->command('commands', html: true);
        $message .= $tg->trans('commands', parameters: $parameters, domain: $domain);

        $tg->reply($message);
    }
}
