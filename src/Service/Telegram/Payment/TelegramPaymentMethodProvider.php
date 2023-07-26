<?php

declare(strict_types=1);

namespace App\Service\Telegram\Payment;

use App\Entity\Telegram\TelegramPaymentMethod;
use App\Enum\Telegram\TelegramPaymentMethodName;

class TelegramPaymentMethodProvider
{
    public function __construct(
        private readonly array $sourcePaymentMethods,
        private ?array $paymentMethods = null,
    )
    {
    }

    /**
     * @param string|null $countryCode
     * @return TelegramPaymentMethod[]
     */
    public function getPaymentMethods(string $countryCode = null): array
    {
        if ($this->paymentMethods === null) {
            $paymentMethods = [];

            foreach ($this->sourcePaymentMethods as $paymentMethodName => $paymentMethod) {
                $paymentMethods[] = new TelegramPaymentMethod(
                    TelegramPaymentMethodName::fromName($paymentMethodName),
                    $paymentMethod['token'],
                    $paymentMethod['currency'],
                    $paymentMethod['countries'] ?? [],
                );
            }

            $this->paymentMethods = $paymentMethods;
        }

        $paymentMethods = $this->paymentMethods;

        if ($countryCode !== null) {
            $paymentMethods = array_filter(
                $paymentMethods,
                fn (TelegramPaymentMethod $paymentMethod) => count($paymentMethod->getCountries()) === 0 || in_array($countryCode, $paymentMethod->getCountries(), true)
            );
        }

        return $paymentMethods;
    }

    public function getPaymentMethod(TelegramPaymentMethodName $paymentMethodName): ?TelegramPaymentMethod
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            if ($paymentMethod->getName() === $paymentMethodName) {
                return $paymentMethod;
            }
        }

        return null;
    }
}