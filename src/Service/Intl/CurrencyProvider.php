<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

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
     * @throws ExceptionInterface
     */
    public function getCurrencies(): array
    {
        if ($this->currencies === null) {
            $content = file_get_contents($this->dataPath);
            $data = json_decode($content, true);

            $this->currencies = array_map(fn ($data) => $this->denormalizer->denormalize($data, Currency::class), $data);
        }

        return $this->currencies;
    }
}