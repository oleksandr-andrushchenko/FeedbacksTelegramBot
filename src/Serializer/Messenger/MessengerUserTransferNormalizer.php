<?php

declare(strict_types=1);

namespace App\Serializer\Messenger;

use App\Enum\Messenger\Messenger;
use App\Object\Messenger\MessengerUserTransfer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MessengerUserTransferNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        /** @var MessengerUserTransfer $object */
        return [
            'messenger' => $object->getMessenger()->value,
            'identifier' => $object->getId(),
            'username' => $object->getUsername(),
            'name' => $object->getName(),
            'locale' => $object->getLocaleCode(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof MessengerUserTransfer;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): MessengerUserTransfer
    {
        return new $type(
            Messenger::from($data['messenger']),
            $data['identifier'],
            $data['username'] ?? null,
            $data['name'] ?? null,
            $data['locale'] ?? null
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === MessengerUserTransfer::class;
    }
}