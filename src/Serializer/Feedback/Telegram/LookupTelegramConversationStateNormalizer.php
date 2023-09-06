<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram;

use App\Entity\Feedback\Telegram\LookupTelegramConversationState;
use App\Entity\Telegram\TelegramConversationState;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LookupTelegramConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $searchConversationStateNormalizer,
        private readonly DenormalizerInterface $searchConversationStateDenormalizer,
    )
    {
    }

    /**
     * @param LookupTelegramConversationState $object
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
        return $data instanceof LookupTelegramConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramConversationState
    {
        return $this->searchConversationStateDenormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === LookupTelegramConversationState::class;
    }
}