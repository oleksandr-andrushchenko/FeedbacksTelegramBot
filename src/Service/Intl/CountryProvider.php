<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Country;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class CountryProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $dataPath,
        private DenormalizerInterface $denormalizer,
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
     * @param string|null $locale
     * @return Country[]
     * @throws ExceptionInterface
     */
    public function getCountries(string $locale = null): array
    {
        if ($this->countries === null) {
            $content = file_get_contents($this->dataPath);
            $data = json_decode($content, true);

            $this->countries = array_map(fn ($data) => $this->denormalizer->denormalize($data, Country::class), $data);
        }

        $countries = $this->countries;

        if ($locale !== null) {
            $countries = array_filter($countries, fn (Country $country) => in_array($locale, $country->getLocales(), true));
        }

        return $locale === null ? $countries : array_values($countries);
    }
}