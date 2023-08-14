<?php

declare(strict_types=1);

namespace App\Service\Site;

use App\Entity\Intl\Locale;
use App\Enum\Site\SitePage;
use App\Enum\Telegram\TelegramGroup;
use App\Service\ContactOptionsFactory;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

class SiteViewResponseFactory
{
    public function __construct(
        private readonly Environment $twig,
        private readonly LocaleSwitcher $localeSwitcher,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly ContactOptionsFactory $contactOptionsFactory,
    )
    {
    }

    public function createViewResponse(SitePage $page): Response
    {
        $localeCode = $this->localeSwitcher->getLocale();

        if (strlen($localeCode) !== 2) {
            throw new NotFoundHttpException();
        }

        $locale = $this->localeProvider->getLocale($localeCode);

        if ($locale === null) {
            $country = $this->countryProvider->getCountry($localeCode);

            if ($country === null) {
                throw new NotFoundHttpException();
            }

            $locale = $this->localeProvider->getLocale($country->getLocaleCodes()[0]);
            $this->localeSwitcher->setLocale($locale->getCode());
        }

        $supportedLocales = $this->localeProvider->getLocales(true);

        if (!in_array($locale->getCode(), array_map(fn (Locale $locale) => $locale->getCode(), $supportedLocales), true)) {
            throw new NotFoundHttpException();
        }

        if ($page === SitePage::PRIVACY_POLICY || $page === SitePage::TERMS_OF_USE) {
            $template = 'site.' . $page->value . '.' . $locale->getCode() . '.html.twig';
        } else {
            $template = 'site.' . $page->value . '.html.twig';
        }

        $localMap = fn ($locale) => [
            'code' => $locale->getCode(),
            'icon' => $this->localeProvider->getLocaleIcon($locale),
            'name' => $this->localeProvider->getLocaleName($locale),
        ];

        return new Response($this->twig->render($template, [
            'pages' => array_diff(array_map(fn ($page) => $page->value, SitePage::cases()), [SitePage::INDEX->value]),
            'page' => $page->value,
            'contacts' => $this->contactOptionsFactory->createContactOptions(TelegramGroup::feedbacks, $locale->getCode()),
            'locales' => array_map($localMap, $supportedLocales),
            'locale' => $localMap($locale),
        ]));
    }
}