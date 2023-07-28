<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Country;
use Symfony\Contracts\Translation\TranslatorInterface;

class CountryProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly array $data,
        private ?array $countries = null,
    )
    {
    }

    public function getCountry(string $code): ?Country
    {
        foreach ($this->getCountries() as $country) {
            if ($country->getCode() === $code) {
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

    public function getCountryName(Country $country, string $locale = null): string
    {
        return $this->translator->trans($country->getCode(), domain: 'countries', locale: $locale);
    }

    /**
     * @param string|null $languageCode
     * @return Country[]
     */
    public function getCountries(string $languageCode = null): array
    {
        if ($this->countries === null) {
            $countries = [];

            foreach ($this->data as $code => $country) {
                $countries[] = new Country(
                    $code,
                    $country['currency'],
                    $country['locales'] ?? []
                );
            }

            $this->countries = $countries;
        }

        $countries = $this->countries;

        if ($languageCode !== null) {
            $countries = array_filter($countries, fn (Country $country) => in_array($languageCode, $country->getLocales(), true));
        }

        return $countries;
    }
}