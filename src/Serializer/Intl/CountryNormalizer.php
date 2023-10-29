<?php

declare(strict_types=1);

namespace App\Serializer\Intl;

use App\Entity\Intl\Country;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CountryNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const LEVEL_1_REGIONS_DUMPED_KEY = 'l1rd';

    /**
     * @param Country $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if ($format === 'internal') {
            return array_filter([
                'c' => $object->getCode(),
                'cu' => $object->getCurrencyCode(),
                'l' => $object->getLocaleCodes(),
                'p' => $object->getPhoneCode(),
                't' => $object->getTimezones(),
                self::LEVEL_1_REGIONS_DUMPED_KEY => $object->level1RegionsDumped() ? true : null,
            ]);
        }
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Country && in_array($format, ['internal'], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Country
    {
        if ($format === 'internal') {
            return new $type(
                $data['c'],
                $data['cu'],
                $data['l'],
                $data['p'],
                $data['t'],
                $data[self::LEVEL_1_REGIONS_DUMPED_KEY] ?? false,
            );
        }
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === Country::class && in_array($format, ['internal'], true);
    }
}