<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram;

use App\Entity\Feedback\Telegram\CreateFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramConversationState;
use App\Enum\Feedback\Rating;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CreateFeedbackTelegramConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $searchConversationStateNormalizer,
        private readonly DenormalizerInterface $searchConversationStateDenormalizer,
    )
    {
    }

    /**
     * @param CreateFeedbackTelegramConversationState $object
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return array_merge($this->searchConversationStateNormalizer->normalize($object, $format, $context), [
            'rating' => $object->getRating()?->value,
            'description' => $object->getDescription(),
            'feedback_id' => $object->getFeedbackId(),
        ]);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof CreateFeedbackTelegramConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramConversationState
    {
        /** @var CreateFeedbackTelegramConversationState $object */
        $object = $this->searchConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $object
            ->setRating(isset($data['rating']) ? Rating::from($data['rating']) : null)
            ->setDescription($data['description'] ?? null)
            ->setFeedbackId($data['feedback_id'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === CreateFeedbackTelegramConversationState::class;
    }
}