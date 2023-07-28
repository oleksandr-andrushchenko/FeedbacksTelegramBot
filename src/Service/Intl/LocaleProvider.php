<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Locale;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleProvider
{
    public function __construct(
        private readonly string $defaultCode,
        private readonly TranslatorInterface $translator,
        private readonly array $data,
        private readonly CountryProvider $countryProvider,
        private ?array $locales = null,
    )
    {
    }

    public function getDefaultLocale(): Locale
    {
        return $this->getLocale($this->defaultCode);
    }

    public function getLocale(string $code): ?Locale
    {
        foreach ($this->getLocales() as $locale) {
            if ($locale->getCode() === $code) {
                return $locale;
            }
        }

        return null;
    }

    public function getLocaleIcon(Locale $locale): string
    {
        $country = $this->countryProvider->getCountry($locale->getCountry());

        return $this->countryProvider->getCountryIcon($country);
    }

    public function getLocaleName(Locale $localeObj, string $locale = null): string
    {
        return $this->translator->trans($localeObj->getCode(), domain: 'locales', locale: $locale);
    }

    /**
     * @return Locale[]
     */
    public function getLocales(): array
    {
        if ($this->locales === null) {
            $locales = [];

            foreach ($this->data as $code => $locale) {
                $locales[] = new Locale(
                    $code,
                    $locale['country'] ?? $code
                );
            }

            $this->locales = $locales;
        }

        return $this->locales;
    }
}