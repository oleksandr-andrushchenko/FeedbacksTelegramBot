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
            $data = [
                'messenger_username' => sprintf('@%s', $object->getMessengerUser()->getUsername()),
                'messenger' => $object->getMessengerUser()->getMessenger()->name,
            ];

            foreach ($object->getSearchTerms() as $index => $searchTerm) {
                $data[sprintf('term_%d', $index)] = [
                    'text' => $searchTerm->getText(),
                    'type' => $searchTerm->getType()->name,
                ];
            }

            $data = array_merge($data, [
                'rate' => $object->getRating()->name,
                'description' => $object->getDescription(),
                'created_at' => $object->getCreatedAt()->getTimestamp(),
            ]);

            return $data;
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Feedback && in_array($format, ['activity'], true);
    }
}