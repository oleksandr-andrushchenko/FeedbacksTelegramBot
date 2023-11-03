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
            $user = $object->getMessengerUser();

            $data = [];

            if (empty($user->getUsername())) {
                $data['user'] = sprintf('@%s', $user->getUsername());
            }

            foreach ($object->getSearchTerms() as $searchTerm) {
                $data[$searchTerm->getType()->name] = $searchTerm->getText();
            }

            $data['rate'] = $object->getRating()->name;
            $data['description'] = $object->getDescription();
            $data['bot'] = sprintf('@%s', $object->getTelegramBot()->getUsername());

            return $data;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Feedback && in_array($format, ['activity'], true);
    }
}