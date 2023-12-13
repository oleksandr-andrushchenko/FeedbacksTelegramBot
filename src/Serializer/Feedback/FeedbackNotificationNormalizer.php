<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Entity\Feedback\FeedbackNotification;

class FeedbackNotificationNormalizer implements NormalizerInterface
{
    /**
     * @param FeedbackNotification $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $data = [];

            $data['type'] = $object->getType()->name;

            $user = $object->getMessengerUser();

            if (!empty($user->getUsername())) {
                $data['user'] = sprintf('@%s', $user->getUsername());
            }

            $searchTerm = $object->getFeedbackSearchTerm();

            if ($searchTerm !== null) {
                $data[$searchTerm->getType()->name] = $searchTerm->getText();
            }

            $data['bot'] = sprintf('@%s', $object->getTelegramBot()->getUsername());

            return $data;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof FeedbackNotification && in_array($format, ['activity'], true);
    }
}