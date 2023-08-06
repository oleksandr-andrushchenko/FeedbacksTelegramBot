<?php

declare(strict_types=1);

namespace App\Serializer\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Enum\Messenger\Messenger;
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
            'locale' => $object->getLocaleCode(),
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
            ->setLocaleCode($data['locale'] ?? null)
            ->setId($data['id'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === MessengerUser::class;
    }
}