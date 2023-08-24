<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Intl\CountryProvider;
use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class TelegramTextsUpdater
{
    public function __construct(
        private string $stage,
        private readonly TelegramRegistry $registry,
        private readonly TranslatorInterface $translator,
        private readonly SiteUrlGenerator $siteUrlGenerator,
        private readonly Environment $twig,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function updateTelegramDescriptions(TelegramBot $bot): void
    {
        $telegram = $this->registry->getTelegram($bot->getUsername());

        $domain = 'tg.texts';
        $group = $telegram->getBot()->getGroup()->name;
        $countryCode = $telegram->getBot()->getCountryCode();
        $country = $this->countryProvider->getCountry($countryCode);

        $transLocaleCode = $country->getLocaleCodes()[0] ?? null;
        $localeCode = $transLocaleCode;

        $name = $this->translator->trans(sprintf('%s.name', $group), domain: $domain, locale: $transLocaleCode);
        $telegram->setMyName([
            'name' => $this->stage === 'prod' ? $name : sprintf('(%s, %s) %s', ucfirst($this->stage), $bot->getPrimaryBot() === null ? 'Primary' : 'Mirror', $name),
            'language_code' => $localeCode,
        ]);

        $description = $this->twig->render('tg.description.html.twig', [
            'locale' => $transLocaleCode,
            'title' => $this->translator->trans(sprintf('%s.description', $group), domain: $domain, locale: $transLocaleCode),
            'privacy_policy_link' => $this->siteUrlGenerator->generate(
                'app.site_privacy_policy',
                [
                    '_locale' => $country->getCode(),
                ],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'terms_of_use_link' => $this->siteUrlGenerator->generate(
                'app.site_terms_of_use',
                [
                    '_locale' => $country->getCode(),
                ],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);
        $telegram->setMyDescription([
            'description' => $description,
            'language_code' => $localeCode,
        ]);

        $shortDescription = $this->translator->trans(sprintf('%s.short_description', $group), domain: $domain, locale: $transLocaleCode);
        $telegram->setMyShortDescription([
            'short_description' => $shortDescription,
            'language_code' => $localeCode,
        ]);
    }
}