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
        private readonly array $supported,
        private ?array $locales = null,
    )
    {
    }

    public function getDefaultLocale(): Locale
    {
        return $this->getLocale($this->defaultCode);
    }

    public function hasLocale(string $code): bool
    {
        foreach ($this->getLocales() as $locale) {
            if ($locale->getCode() === $code) {
                return true;
            }
        }

        return false;
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
        $country = $this->countryProvider->getCountry($locale->getFlag());

        return $this->countryProvider->getCountryIcon($country);
    }

    public function getLocaleName(Locale $localeObj, string $locale = null): string
    {
        return $this->translator->trans($localeObj->getCode(), domain: 'locales', locale: $locale);
    }

    /**
     * @param bool|null $supported
     * @param string|null $country
     * @return Locale[]
     */
    public function getLocales(bool $supported = null, string $country = null): array
    {
        if ($this->locales === null) {
            $locales = [];

            foreach ($this->data as $code => $locale) {
                $locales[] = new Locale(
                    $code,
                    $locale['flag'] ?? $code
                );
            }

            $this->locales = $locales;
        }

        $locales = $this->locales;

        if ($supported) {
            $locales = array_filter($locales, fn (Locale $locale) => in_array($locale->getCode(), $this->supported, true));
        }

        if ($country !== null) {
            $filter = $this->countryProvider->getCountry($country)->getLocaleCodes() ?? [];
            $locales = array_intersect($locales, $filter);
        }

        return $supported === null && $country === null ? $locales : array_values($locales);
    }
}