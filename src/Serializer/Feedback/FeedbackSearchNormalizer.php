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
            $user = $object->getMessengerUser();

            $data = [];

            if (!empty($user->getUsername())) {
                $data['user'] = sprintf('@%s', $user->getUsername());
            }

            $data[$object->getSearchTerm()->getType()->name] = $object->getSearchTerm()->getText();

            $data['bot'] = sprintf('@%s', $object->getTelegramBot()->getUsername());

            return $data;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof FeedbackSearch && in_array($format, ['activity'], true);
    }
}