<?php

declare(strict_types=1);

namespace App\Serializer\Feedback;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
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

    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        /** @var SearchTermTransfer $object */
        return [
            'text' => $object->getText(),
            'normalized_text' => $object->getNormalizedText(),
            'type' => $object->getType()?->value,
            'messenger' => $object->getMessenger()?->value,
            'messenger_profile_url' => $object->getMessengerProfileUrl(),
            'messenger_username' => $object->getMessengerUsername(),
            'messenger_user' => $object->getMessengerUser() === null ? null : $this->messengerUserTransferNormalizer->normalize($object->getMessengerUser(), $format, $context),
            'possible_types' => $object->getPossibleTypes() === null ? null : array_map(static fn ($type): int => $type->value, $object->getPossibleTypes()),
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
            ->setMessenger(isset($data['messenger']) ? Messenger::from($data['messenger']) : null)
            ->setMessengerProfileUrl($data['messenger_profile_url'] ?? null)
            ->setMessengerUsername($data['messenger_username'] ?? null)
            ->setMessengerUser(isset($data['messenger_user']) ? $this->messengerUserTransferDenormalizer->denormalize($data['messenger_user'], MessengerUserTransfer::class, $format, $context) : null)
            ->setPossibleTypes(isset($data['possible_types']) ? array_map(static fn ($type): SearchTermType => SearchTermType::from($type), $data['possible_types']) : null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === SearchTermTransfer::class;
    }
}