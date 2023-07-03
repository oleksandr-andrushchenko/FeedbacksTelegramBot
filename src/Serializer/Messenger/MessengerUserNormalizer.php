<?php

declare(strict_types=1);

namespace App\Serializer\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Enum\Messenger\Messenger;
use DateTimeImmutable;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MessengerUserNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        /** @var MessengerUser $object */
        return [
            'messenger' => $object->getMessenger()?->value,
            'identifier' => $object->getIdentifier(),
            'username' => $object->getUsername(),
            'name' => $object->getName(),
            'language_code' => $object->getLanguageCode(),
            'created_at' => $object->getCreatedAt()?->getTimestamp(),
            'updated_at' => $object->getUpdatedAt()?->getTimestamp(),
            'id' => $object->getId(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof MessengerUser;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): MessengerUser
    {
        /** @var MessengerUser $object */
        $object = new $type(Messenger::from($data['messenger']), $data['identifier']);

        $object
            ->setUsername($data['username'] ?? null)
            ->setName($data['name'] ?? null)
            ->setLanguageCode($data['language_code'] ?? null)
            ->setId($data['id'] ?? null)
            ->setCreatedAt(isset($data['created_at']) ? (new DateTimeImmutable())->setTimestamp($data['created_at']) : null)
            ->setCreatedAt(isset($data['updated_at']) ? (new DateTimeImmutable())->setTimestamp($data['updated_at']) : null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === MessengerUser::class;
    }
}