<?php

declare(strict_types=1);

namespace App\Serializer\Telegram;

use App\Entity\Telegram\TelegramConversationState;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TelegramConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param TelegramConversationState $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'step' => $object->getStep(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof TelegramConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramConversationState
    {
        /** @var TelegramConversationState $object */
        $object = new $type();

        $object
            ->setStep($data['step'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && ($type === TelegramConversationState::class || get_parent_class($type) === TelegramConversationState::class);
    }
}