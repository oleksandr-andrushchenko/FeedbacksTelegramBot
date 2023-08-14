<?php

declare(strict_types=1);

namespace App\Service\Telegram\Payment;

use App\Entity\Telegram\TelegramPaymentMethod;
use App\Exception\Intl\CurrencyNotFoundException;
use App\Object\Telegram\Payment\TelegramPaymentMethodTransfer;
use App\Service\Intl\CurrencyProvider;
use Doctrine\ORM\EntityManagerInterface;

class TelegramPaymentMethodCreator
{
    public function __construct(
        private readonly CurrencyProvider $currencyProvider,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @param TelegramPaymentMethodTransfer $paymentMethodTransfer
     * @return TelegramPaymentMethod
     * @throws CurrencyNotFoundException
     */
    public function createTelegramPaymentMethod(TelegramPaymentMethodTransfer $paymentMethodTransfer): TelegramPaymentMethod
    {
        $currencyCodes = $paymentMethodTransfer->getCurrencies();
        foreach ($currencyCodes as $currencyCode) {
            if (!$this->currencyProvider->hasCurrency($currencyCode)) {
                throw new CurrencyNotFoundException($currencyCode);
            }
        }

        $paymentMethod = new TelegramPaymentMethod(
            $paymentMethodTransfer->getBot(),
            $paymentMethodTransfer->getName(),
            $paymentMethodTransfer->getToken(),
            $currencyCodes,
        );
        $this->entityManager->persist($paymentMethod);

        return $paymentMethod;
    }
}