<?php

declare(strict_types=1);

namespace App\Serializer\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Enum\Telegram\TelegramBotPaymentMethodName;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TelegramBotPaymentMethodNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param TelegramBotPaymentMethod $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'name' => $object->getName()->value,
            'currency' => $object->getCurrency(),
            'countries' => $object->getCountries() === null ? null : $object->getCountries(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof TelegramBotPaymentMethod;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotPaymentMethod
    {
        return new $type(
            TelegramBotPaymentMethodName::from($data['name']),
            '***',
            $data['currency'],
            $data['countries'] ?? null,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === TelegramBotPaymentMethod::class;
    }
}