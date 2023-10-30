<?php

declare(strict_types=1);

namespace App\Serializer\User;

use App\Entity\User\UserContactMessage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserContactMessageNormalizer implements NormalizerInterface
{
    /**
     * @param UserContactMessage $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            return [
                'messenger_username' => sprintf('@%s', $object->getMessengerUser()->getUsername()),
                'messenger' => $object->getMessengerUser()->getMessenger()->name,
                'text' => $object->getText(),
                'bot' => sprintf('@%s', $object->getTelegramBot()->getUsername()),
                'created_at' => $object->getCreatedAt()->getTimestamp(),
            ];
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof UserContactMessage && in_array($format, ['activity'], true);
    }
}