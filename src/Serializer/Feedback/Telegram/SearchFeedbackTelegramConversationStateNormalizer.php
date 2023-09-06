<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram;

use App\Entity\Feedback\Telegram\SearchFeedbackTelegramConversationState;
use App\Entity\Telegram\TelegramConversationState;
use App\Object\Feedback\SearchTermTransfer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SearchFeedbackTelegramConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
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
     * @param SearchFeedbackTelegramConversationState $object
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return array_merge($this->baseConversationStateNormalizer->normalize($object, $format, $context), [
            'search_term' => $object->getSearchTerm() === null ? null : $this->searchTermTransferNormalizer->normalize($object->getSearchTerm(), $format, $context),
            'change' => $object->isChange(),
        ]);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof SearchFeedbackTelegramConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramConversationState
    {
        /** @var SearchFeedbackTelegramConversationState $object */
        $object = $this->baseConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $object
            ->setSearchTerm(isset($data['search_term']) ? $this->searchTermTransferDenormalizer->denormalize($data['search_term'], SearchTermTransfer::class, $format, $context) : null)
            ->setChange($data['change'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === SearchFeedbackTelegramConversationState::class;
    }
}