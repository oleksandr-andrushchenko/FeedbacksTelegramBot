<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Enum\Feedback\SearchTermType;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SearchTermTransferNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $messengerUserTransferNormalizer,
        private readonly DenormalizerInterface $messengerUserTransferDenormalizer,
    )
    {
    }

    /**
     * @param SearchTermTransfer $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'text' => $object->getText(),
            'normalized_text' => $object->getNormalizedText(),
            'type' => $object->getType()?->value,
            'messenger_user' => $object->getMessengerUser() === null ? null : $this->messengerUserTransferNormalizer->normalize($object->getMessengerUser(), $format, $context),
            'types' => $object->getTypes() === null ? null : array_map(static fn ($type): int => $type->value, $object->getTypes()),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof SearchTermTransfer;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): SearchTermTransfer
    {
        /** @var SearchTermTransfer $object */
        $object = new $type($data['text']);

        $object
            ->setNormalizedText($data['normalized_text'] ?? null)
            ->setType(isset($data['type']) ? SearchTermType::from($data['type']) : null)
            ->setMessengerUser(isset($data['messenger_user']) ? $this->messengerUserTransferDenormalizer->denormalize($data['messenger_user'], MessengerUserTransfer::class, $format, $context) : null)
            ->setTypes(isset($data['types']) ? array_map(static fn ($type): SearchTermType => SearchTermType::from($type), $data['types']) : null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === SearchTermTransfer::class;
    }
}