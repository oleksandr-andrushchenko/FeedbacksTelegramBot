<?php

declare(strict_types=1);

namespace App\Service\Site;

use App\Entity\Site\SiteContactOptions;
use App\Enum\Site\SitePage;
use App\Service\Intl\LocaleProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

class SiteViewResponseFactory
{
    public function __construct(
        private readonly Environment $twig,
        private readonly LocaleSwitcher $localeSwitcher,
        private readonly LocaleProvider $localeProvider,
        private readonly SiteContactOptions $contactOptions,
    )
    {
    }

    public function createViewResponse(SitePage $page): Response
    {
        $locale = null;

        if ($page === SitePage::PRIVACY_POLICY || $page === SitePage::TERMS_OF_USE) {
            $locale = $this->localeSwitcher->getLocale();
        }

        $localMap = fn ($locale) => [
            'code' => $locale->getCode(),
            'icon' => $this->localeProvider->getLocaleIcon($locale),
            'name' => $this->localeProvider->getLocaleName($locale),
        ];

        return new Response($this->twig->render($page->view($locale), [
            'pages' => array_diff(array_map(fn ($page) => $page->value, SitePage::cases()), [SitePage::INDEX->value]),
            'page' => $page->value,
            'contacts' => $this->contactOptions,
            'locales' => array_map($localMap, $this->localeProvider->getLocales(true)),
            'locale' => $localMap($this->localeProvider->getLocale($this->localeSwitcher->getLocale())),
        ]));
    }
}