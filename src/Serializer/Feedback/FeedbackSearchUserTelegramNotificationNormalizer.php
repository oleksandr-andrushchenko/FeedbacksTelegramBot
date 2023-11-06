<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\FeedbackSearchUserTelegramNotification;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackSearchUserTelegramNotificationNormalizer implements NormalizerInterface
{
    /**
     * @param FeedbackSearchUserTelegramNotification $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $data = [];

            $user = $object->getMessengerUser();

            if (!empty($user->getUsername())) {
                $data['user'] = sprintf('@%s', $user->getUsername());
            }

            $searchTerm = $object->getFeedbackSearchTerm();

            $data[$searchTerm->getType()->name] = $searchTerm->getText();

            $data['bot'] = sprintf('@%s', $object->getTelegramBot()->getUsername());

            return $data;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof FeedbackSearchUserTelegramNotification && in_array($format, ['activity'], true);
    }
}