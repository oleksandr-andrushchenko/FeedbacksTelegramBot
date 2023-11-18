<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackUserSubscriptionNormalizer implements NormalizerInterface
{
    /**
     * @param FeedbackUserSubscription $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            $user = $object->getMessengerUser();

            $data = [];

            if (!empty($user?->getUsername())) {
                $data['user'] = sprintf('@%s', $user->getUsername());
            }

            $data['plan'] = $object->getSubscriptionPlan()->name;
            $data['bot'] = sprintf('@%s', $object->getTelegramBot()->getUsername());

            return $data;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof FeedbackUserSubscription && in_array($format, ['activity'], true);
    }
}