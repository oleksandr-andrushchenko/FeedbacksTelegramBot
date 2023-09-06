<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Locale;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly array $data,
        private readonly CountryProvider $countryProvider,
        private readonly array $supported,
    )
    {
    }

    public function hasLocale(string $code): bool
    {
        return array_key_exists($code, $this->data);
    }

    public function getLocale(string $code): ?Locale
    {
        if (!$this->hasLocale($code)) {
            return null;
        }

        return $this->denormalize($this->data[$code]);
    }

    public function getLocaleIcon(Locale $locale): string
    {
        $country = $this->countryProvider->getCountry($locale->getFlag());

        return $this->countryProvider->getCountryIcon($country);
    }

    public function getUnknownLocaleIcon(): string
    {
        return $this->countryProvider->getUnknownCountryIcon();
    }

    public function getLocaleName(Locale $localeObj, string $localeCode = null): string
    {
        return $this->translator->trans($localeObj->getCode(), domain: 'locale', locale: $localeCode);
    }

    public function getUnknownLocaleName(string $locale = null): string
    {
        return $this->translator->trans('zz', domain: 'locale', locale: $locale);
    }

    public function getLocaleComposeName(Locale $localeObj = null, string $localeCode = null): string
    {
        if ($localeObj === null) {
            return join(' ', [
                $this->getUnknownLocaleIcon(),
                $this->getUnknownLocaleName($localeCode),
            ]);
        }

        return join(' ', [
            $this->getLocaleIcon($localeObj),
            $this->getLocaleName($localeObj, $localeCode),
        ]);
    }

    /**
     * @param bool|null $supported
     * @param string|null $countryCode
     * @return Locale[]
     */
    public function getLocales(bool $supported = null, string $countryCode = null): array
    {
        $data = $this->data;

        if ($supported) {
            $data = array_filter($data, fn ($code) => in_array($code, $this->supported, true), ARRAY_FILTER_USE_KEY);
        }

        if ($countryCode !== null) {
            $filter = $this->countryProvider->getCountry($countryCode)->getLocaleCodes() ?? [];
            $data = array_filter($data, fn ($code) => in_array($code, $filter, true), ARRAY_FILTER_USE_KEY);
        }

        return array_map(fn ($record) => $this->denormalize($record), $supported === null && $countryCode === null ? $data : array_values($data));
    }

    private function denormalize(array $record): Locale
    {
        return new Locale(
            $code = $record['code'],
            $record['flag'] ?? $code
        );
    }
}