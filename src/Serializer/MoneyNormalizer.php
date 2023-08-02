<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Money;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizer implements NormalizerInterface
{
    /**
     * @param Money $object
     * @param string|null $format
     * @param array $context
     * @return string[]
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'amount' => $object->getAmount(),
            'currency' => $object->getCurrency(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Money;
    }
}