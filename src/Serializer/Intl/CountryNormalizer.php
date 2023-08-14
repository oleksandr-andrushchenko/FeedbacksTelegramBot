<?php

declare(strict_types=1);

namespace App\Serializer\Intl;

use App\Entity\Intl\Country;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CountryNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param Country $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'code' => $object->getCode(),
            'currency' => $object->getCurrencyCode(),
            'locales' => $object->getLocaleCodes(),
            'phone' => $object->getPhoneCode(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Country;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Country
    {
        return new $type(
            $data['code'],
            $data['currency'],
            $data['locales'],
            $data['phone']
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === Country::class;
    }
}