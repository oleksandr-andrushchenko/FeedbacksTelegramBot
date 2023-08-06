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
     * @param string|null $country
     * @return TelegramPaymentMethod[]
     */
    public function getPaymentMethods(string $country = null): array
    {
        if ($this->paymentMethods === null) {
            $paymentMethods = [];

            foreach ($this->sourcePaymentMethods as $paymentMethodName => $paymentMethod) {
                $paymentMethods[] = new TelegramPaymentMethod(
                    TelegramPaymentMethodName::fromName($paymentMethodName),
                    $paymentMethod['token'],
                    $paymentMethod['currency'],
                    $paymentMethod['countries'] ?? [],
                    $paymentMethod['flag'] ?? null,
                );
            }

            $this->paymentMethods = $paymentMethods;
        }

        $paymentMethods = $this->paymentMethods;

        if ($country !== null) {
            $paymentMethods = array_values(array_filter(
                $paymentMethods,
                fn (TelegramPaymentMethod $paymentMethod) => $paymentMethod->isGlobal() || in_array($country, $paymentMethod->getCountries(), true)
            ));
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