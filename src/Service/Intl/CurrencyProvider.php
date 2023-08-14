<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CurrencyProvider
{
    public function __construct(
        private readonly string $dataPath,
        private DenormalizerInterface $denormalizer,
        private readonly TranslatorInterface $translator,
        private readonly CountryProvider $countryProvider,
        private ?array $currencies = null,
    )
    {
    }

    public function hasCurrency(string $code): bool
    {
        foreach ($this->getCurrencies() as $currency) {
            if ($currency->getCode() === $code) {
                return true;
            }
        }

        return false;
    }

    public function getCurrency(string $code): ?Currency
    {
        foreach ($this->getCurrencies() as $currency) {
            if ($currency->getCode() === $code) {
                return $currency;
            }
        }

        return null;
    }

    public function getCurrencyIcon(Currency $currency): string
    {
        $code = match ($currency->getCode()) {
            'EUR' => 'eu',
            'USD' => 'us',
            default => null,
        };
        if ($code === null) {
            $country = $this->countryProvider->getCountryByCurrency($currency->getCode());
            $code = $country->getCode();
        }

        return "\xF0\x9F\x87" . chr(ord($code[0]) + 0x45) . "\xF0\x9F\x87" . chr(ord($code[1]) + 0x45);
    }

    public function getCurrencyName(Currency $currency, string $locale = null): string
    {
        return $this->translator->trans($currency->getCode(), domain: 'currencies', locale: $locale);
    }

    /**
     * @param array|null $currencyCodes
     * @return Currency[]
     * @throws ExceptionInterface
     */
    public function getCurrencies(array $currencyCodes = null): array
    {
        if ($this->currencies === null) {
            $content = file_get_contents($this->dataPath);
            $data = json_decode($content, true);

            $this->currencies = array_map(fn ($data) => $this->denormalizer->denormalize($data, Currency::class), $data);
        }

        $currencies = $this->currencies;

        if ($currencyCodes !== null) {
            $currencies = array_filter(
                $currencies,
                fn (Currency $currency) => in_array($currency->getCode(), $currencyCodes, true)
            );
        }

        return $currencies;
    }
}