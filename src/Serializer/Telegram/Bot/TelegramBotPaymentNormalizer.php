<?php

declare(strict_types=1);

namespace App\Serializer\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPayment;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TelegramBotPaymentNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $priceNormalizer,
    )
    {
    }

    /**
     * @param TelegramBotPayment $object
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            return [
                'messenger_user' => $object->getMessengerUser()->getUsername(),
                'method' => $object->getMethod()->getName()->name,
                'purpose' => $object->getPurpose(),
                'price' => $this->priceNormalizer->normalize($object->getPrice(), $format, $context),
                'has_pre_checkout_query' => $object->getPreCheckoutQuery() !== null,
                'has_successful_payment' => $object->getSuccessfulPayment() !== null,
                'created_at' => $object->getCreatedAt()->getTimestamp(),
                'updated_at' => $object->getUpdatedAt()?->getTimestamp(),
            ];
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof TelegramBotPayment && in_array($format, ['activity'], true);
    }
}