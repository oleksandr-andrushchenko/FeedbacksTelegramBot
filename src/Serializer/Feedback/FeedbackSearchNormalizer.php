<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackSearchNormalizer implements NormalizerInterface
{
    /**
     * @param FeedbackSearch $object
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
                'search_term' => $object->getSearchTerm()->getText(),
                'search_term_type' => $object->getSearchTerm()->getType()->name,
                'bot' => sprintf('@%s', $object->getTelegramBot()->getUsername()),
                'created_at' => $object->getCreatedAt()->getTimestamp(),
            ];
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof FeedbackSearch && in_array($format, ['activity'], true);
    }
}