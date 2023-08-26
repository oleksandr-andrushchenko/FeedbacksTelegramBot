<?php

declare(strict_types=1);

namespace App\Serializer\Telegram;

use App\Entity\Telegram\TelegramConversationState;
use App\Entity\Telegram\CreateFeedbackTelegramConversationState;
use App\Enum\Feedback\Rating;
use App\Object\Feedback\SearchTermTransfer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CreateFeedbackTelegramConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $baseConversationStateNormalizer,
        private readonly DenormalizerInterface $baseConversationStateDenormalizer,
        private readonly NormalizerInterface $searchTermTransferNormalizer,
        private readonly DenormalizerInterface $searchTermTransferDenormalizer,
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
        return array_merge($this->baseConversationStateNormalizer->normalize($object, $format, $context), [
            'search_term' => $object->getSearchTerm() === null ? null : $this->searchTermTransferNormalizer->normalize($object->getSearchTerm(), $format, $context),
            'rating' => $object->getRating()?->value,
            'description' => $object->getDescription(),
            'change' => $object->isChange(),
            'search_term_step' => $object->isSearchTermStep(),
        ]);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof CreateFeedbackTelegramConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramConversationState
    {
        /** @var CreateFeedbackTelegramConversationState $object */
        $object = $this->baseConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $object
            ->setSearchTerm(isset($data['search_term']) ? $this->searchTermTransferDenormalizer->denormalize($data['search_term'], SearchTermTransfer::class, $format, $context) : null)
            ->setRating(isset($data['rating']) ? Rating::from($data['rating']) : null)
            ->setDescription($data['description'] ?? null)
            ->setChange($data['change'] ?? null)
            ->setSearchTermStep($data['search_term_step'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === CreateFeedbackTelegramConversationState::class;
    }
}