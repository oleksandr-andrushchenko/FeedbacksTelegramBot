<?php

declare(strict_types=1);

namespace App\Serializer\Intl;

use App\Entity\Intl\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CurrencyNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param Currency $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'code' => $object->getCode(),
            'rate' => $object->getRate(),
            'exp' => $object->getExp(),
//            'symbol' => $object->getSymbol(),
//            'native' => $object->getNative(),
//            'symbol_left' => $object->isSymbolLeft(),
//            'space_between' => $object->isSpaceBetween(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Currency;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Currency
    {
        return new $type(
            $data['code'],
            $data['rate'],
            $data['exp'],
//            $data['symbol'] ?? null,
//            $data['native'] ?? null,
//            $data['symbol_left'] ?? null,
//            $data['space_between'] ?? null,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === Currency::class;
    }
}