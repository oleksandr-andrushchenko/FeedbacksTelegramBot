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
        private ?array $data = null,
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
        $code = $country->getCode();

        return "\xF0\x9F\x87" . chr(ord($code[0]) + 0x45) . "\xF0\x9F\x87" . chr(ord($code[1]) + 0x45);
    }

    public function getUnknownCountryIcon(): string
    {
        return '🌎';
    }

    public function getCountryName(Country $country, string $locale = null): string
    {
        return $this->translator->trans($country->getCode(), domain: 'countries', locale: $locale);
    }

    public function getUnknownCountryName(string $locale = null): string
    {
        return $this->translator->trans('zz', domain: 'countries', locale: $locale);
    }

    public function getComposeCountryName(Country $country = null, string $locale = null): string
    {
        if ($country === null) {
            return join(' ', [
                $this->getUnknownCountryIcon(),
                $this->getUnknownCountryName($locale),
            ]);
        }

        return join(' ', [
            $this->getCountryIcon($country),
            $this->getCountryName($country, $locale),
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
        if ($this->data === null) {
            $content = file_get_contents($this->dataPath);
            $this->data = json_decode($content, true);
        }

        return $this->data;
    }
}