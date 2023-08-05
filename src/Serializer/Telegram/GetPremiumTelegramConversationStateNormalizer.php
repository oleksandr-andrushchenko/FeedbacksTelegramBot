<?php

declare(strict_types=1);

namespace App\Serializer\Telegram;

use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Entity\Telegram\GetPremiumTelegramConversationState;
use App\Entity\Telegram\TelegramPaymentMethod;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GetPremiumTelegramConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $baseConversationStateNormalizer,
        private readonly DenormalizerInterface $baseConversationStateDenormalizer,
        private readonly NormalizerInterface $subscriptionPlanNormalizer,
        private readonly DenormalizerInterface $subscriptionPlanDenormalizer,
        private readonly NormalizerInterface $paymentMethodNormalizer,
        private readonly DenormalizerInterface $paymentMethodDenormalizer,
    )
    {
    }

    /**
     * @param GetPremiumTelegramConversationState $object
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return array_merge($this->baseConversationStateNormalizer->normalize($object, $format, $context), [
            'subscription_plan' => $object->getSubscriptionPlan() === null ? null : $this->subscriptionPlanNormalizer->normalize($object->getSubscriptionPlan(), $format, $context),
            'payment_method' => $object->getPaymentMethod() === null ? null : $this->paymentMethodNormalizer->normalize($object->getPaymentMethod(), $format, $context),
            'payment_method_step' => $object->isPaymentMethodStep(),
        ]);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof GetPremiumTelegramConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): GetPremiumTelegramConversationState
    {
        /** @var GetPremiumTelegramConversationState $object */
        $object = $this->baseConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $object
            ->setSubscriptionPlan(isset($data['subscription_plan']) ? $this->subscriptionPlanDenormalizer->denormalize($data['subscription_plan'], FeedbackSubscriptionPlan::class, $format, $context) : null)
            ->setPaymentMethod(isset($data['payment_method']) ? $this->paymentMethodDenormalizer->denormalize($data['payment_method'], TelegramPaymentMethod::class, $format, $context) : null)
            ->setIsPaymentMethodStep($data['payment_method_step'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === GetPremiumTelegramConversationState::class;
    }
}