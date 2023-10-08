<?php

declare(strict_types=1);

namespace App\Serializer\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversationState;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TelegramBotConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param TelegramBotConversationState $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'step' => $object->getStep(),
            'skip_help_buttons' => $object->getSkipHelpButtons(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof TelegramBotConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotConversationState
    {
        /** @var TelegramBotConversationState $object */
        $object = new $type();

        $object
            ->setStep($data['step'] ?? null)
            ->setSkipHelpButtons($data['skip_help_buttons'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && ($type === TelegramBotConversationState::class || get_parent_class($type) === TelegramBotConversationState::class);
    }
}