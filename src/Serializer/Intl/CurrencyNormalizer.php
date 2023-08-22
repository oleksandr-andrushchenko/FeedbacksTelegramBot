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
        if ($format === 'internal') {
            return [
                'c' => $object->getCode(),
                'r' => $object->getRate(),
                'e' => $object->getExp(),
//            's' => $object->getSymbol(),
                'n' => $object->getNative(),
                'sl' => $object->isSymbolLeft(),
                'sb' => $object->isSpaceBetween(),
            ];
        }
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Currency && in_array($format, ['internal'], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Currency
    {
        if ($format === 'internal') {
            return new $type(
                $data['c'],
                $data['r'],
                $data['e'],
//            symbol: $data['s'] ?? null,
                native: $data['n'] ?? null,
                symbolLeft: $data['sl'] ?? null,
                spaceBetween: $data['sb'] ?? null,
            );
        }
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === Currency::class && in_array($format, ['internal'], true);
    }
}