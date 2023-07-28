<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CurrencyProvider
{
    public function __construct(
        private readonly string $defaultCode,
        private readonly string $dataPath,
        private DenormalizerInterface $denormalizer,
        private ?array $currencies = null,
    )
    {
    }

    public function getDefaultCurrency(): Currency
    {
        return $this->getCurrency($this->defaultCode);
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

    /**
     * @return Currency[]
     */
    public function getCurrencies(): array
    {
        if ($this->currencies === null) {
            $response = file_get_contents($this->dataPath);
            $data = json_decode($response, true);

            $this->currencies = array_map(fn ($data) => $this->denormalizer->denormalize($data, Currency::class), $data);
        }

        return $this->currencies;
    }
}