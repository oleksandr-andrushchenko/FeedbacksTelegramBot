<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Country;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CountryProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $dataPath,
        private DenormalizerInterface $denormalizer,
    )
    {
    }

    public function hasCountry(string $code): bool
    {
        return array_key_exists($code, $this->getData());
    }

    public function getCountry(string $code): ?Country
    {
        if (!$this->hasCountry($code)) {
            return null;
        }

        return $this->denormalize($this->getData()[$code]);
    }

    public function getCountryByCurrency(string $currencyCode): ?Country
    {
        foreach ($this->getCountries() as $country) {
            if ($country->getCurrencyCode() === $currencyCode) {
                return $country;
            }
        }

        return null;
    }

    public function getCountryIcon(Country $country): string
    {
        return $this->getCountryIconByCode($country->getCode());
    }

    public function getCountryIconByCode(string $code): string
    {
        return "\xF0\x9F\x87" . chr(ord($code[0]) + 0x45) . "\xF0\x9F\x87" . chr(ord($code[1]) + 0x45);
    }

    public function getUnknownCountryIcon(): string
    {
        return '🌎';
    }

    public function getCountryName(Country $country, string $localeCode = null): string
    {
        return $this->translator->trans($country->getCode(), domain: 'country', locale: $localeCode);
    }

    public function getUnknownCountryName(string $localeCode = null): string
    {
        return $this->translator->trans('zz', domain: 'country', locale: $localeCode);
    }

    public function getCountryComposeName(Country $country = null, string $localeCode = null): string
    {
        if ($country === null) {
            return join(' ', [
                $this->getUnknownCountryIcon(),
                $this->getUnknownCountryName($localeCode),
            ]);
        }

        return join(' ', [
            $this->getCountryIcon($country),
            $this->getCountryName($country, $localeCode),
        ]);
    }

    /**
     * @param string|null $localeCode
     * @return Country[]
     */
    public function getCountries(string $localeCode = null): array
    {
        $countries = array_map(fn ($record) => $this->denormalize($record), array_values($this->getData()));

        if ($localeCode !== null) {
            $countries = array_filter($countries, fn ($country) => in_array($localeCode, $country->getLocaleCodes(), true));
        }

        return $localeCode === null ? $countries : array_values($countries);
    }

    private function denormalize(array $record): Country
    {
        return $this->denormalizer->denormalize($record, Country::class, format: 'internal');
    }

    private function getData(): array
    {
        static $data = null;

        if ($data === null) {
            $content = file_get_contents($this->dataPath);
            $data = json_decode($content, true);
        }

        return $data;
    }
}