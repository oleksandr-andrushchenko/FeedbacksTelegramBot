<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\Feedback;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackNormalizer implements NormalizerInterface
{
    /**
     * @param Feedback $object
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
                'search_term' => $object->getSearchTermText(),
                'search_term_type' => $object->getSearchTermType()->name,
                'rate' => $object->getRating()->name,
                'description' => $object->getDescription(),
                'created_at' => $object->getCreatedAt()->getTimestamp(),
            ];
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Feedback && in_array($format, ['activity'], true);
    }
}