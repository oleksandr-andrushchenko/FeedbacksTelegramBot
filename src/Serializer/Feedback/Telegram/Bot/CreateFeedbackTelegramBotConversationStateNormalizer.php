<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram\Bot;

use App\Entity\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Entity\Telegram\TelegramBotConversationState;
use App\Enum\Feedback\Rating;
use App\Transfer\Feedback\SearchTermTransfer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CreateFeedbackTelegramBotConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
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
     * @param CreateFeedbackTelegramBotConversationState $object
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return array_merge($this->baseConversationStateNormalizer->normalize($object, $format, $context), [
            'search_terms' => $object->getSearchTerms() === null ? null : array_map(fn (SearchTermTransfer $searchTerm): array => $this->searchTermTransferNormalizer->normalize($searchTerm, $format, $context), $object->getSearchTerms()),
            'rating' => $object->getRating()?->value,
            'description' => $object->getDescription(),
            'created_id' => $object->getCreatedId(),
        ]);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof CreateFeedbackTelegramBotConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotConversationState
    {
        /** @var CreateFeedbackTelegramBotConversationState $object */
        $object = $this->baseConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $object
            ->setSearchTerms(isset($data['search_terms']) ? array_map(fn (array $searchTerm): SearchTermTransfer => $this->searchTermTransferDenormalizer->denormalize($searchTerm, SearchTermTransfer::class, $format, $context), $data['search_terms']) : null)
            ->setRating(isset($data['rating']) ? Rating::from($data['rating']) : null)
            ->setDescription($data['description'] ?? null)
            ->setCreatedId($data['created_id'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === CreateFeedbackTelegramBotConversationState::class;
    }
}