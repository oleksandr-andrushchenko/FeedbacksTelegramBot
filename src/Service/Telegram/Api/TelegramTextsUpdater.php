<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Site\SitePage;
use App\Service\Intl\CountryProvider;
use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class TelegramTextsUpdater
{
    public function __construct(
        private string $stage,
        private readonly TelegramRegistry $registry,
        private readonly SiteUrlGenerator $siteUrlGenerator,
        private readonly Environment $twig,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function updateTelegramDescriptions(TelegramBot $bot): void
    {
        $telegram = $this->registry->getTelegram($bot);

        $countryCode = $telegram->getBot()->getCountryCode();
        $country = $this->countryProvider->getCountry($countryCode);
        $localeCode = $telegram->getBot()->getLocaleCode();
        $name = $bot->getName();

        $telegram->setMyName([
            'name' => $this->stage === 'prod' ? $name : sprintf('(%s, %s) %s', ucfirst($this->stage), strtoupper($bot->getCountryCode()), $name),
            'language_code' => $localeCode,
        ]);

        $description = $this->twig->render('tg.description.html.twig', [
            'localeCode' => $localeCode,
            'privacy_policy_link' => $this->siteUrlGenerator->generate(
                'app.telegram_site_page',
                [
                    'username' => $bot->getUsername(),
                    'page' => SitePage::PRIVACY_POLICY->value,
                ],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'terms_of_use_link' => $this->siteUrlGenerator->generate(
                'app.telegram_site_page',
                [
                    'username' => $bot->getUsername(),
                    'page' => SitePage::TERMS_OF_USE->value,
                ],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);

        $telegram->setMyDescription([
            'description' => $description,
            'language_code' => $localeCode,
        ]);

        $telegram->setMyShortDescription([
            'short_description' => $description,
            'language_code' => $localeCode,
        ]);
    }
}