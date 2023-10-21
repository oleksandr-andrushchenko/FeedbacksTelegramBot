<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram\Bot;

use App\Entity\Feedback\Telegram\Bot\LookupFeedbackTelegramBotConversationState;
use App\Entity\Telegram\TelegramBotConversationState;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LookupFeedbackTelegramBotConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $searchConversationStateNormalizer,
        private readonly DenormalizerInterface $searchConversationStateDenormalizer,
    )
    {
    }

    /**
     * @param LookupFeedbackTelegramBotConversationState $object
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return $this->searchConversationStateNormalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof LookupFeedbackTelegramBotConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotConversationState
    {
        return $this->searchConversationStateDenormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === LookupFeedbackTelegramBotConversationState::class;
    }
}