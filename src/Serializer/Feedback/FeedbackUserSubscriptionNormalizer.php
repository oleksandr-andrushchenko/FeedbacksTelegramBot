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
            return [
                'messenger_username' => sprintf('@%s', $object->getMessengerUser()->getUsername()),
                'messenger' => $object->getMessengerUser()->getMessenger()->name,
                'plan' => $object->getSubscriptionPlan()->name,
                'bot' => sprintf('@%s', $object->getTelegramBot()->getUsername()),
                'created_at' => $object->getCreatedAt()->getTimestamp(),
            ];
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof FeedbackUserSubscription && in_array($format, ['activity'], true);
    }
}