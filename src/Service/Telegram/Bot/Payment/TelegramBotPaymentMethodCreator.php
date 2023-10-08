<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Payment;

use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Exception\Intl\CurrencyNotFoundException;
use App\Transfer\Telegram\TelegramBotPaymentMethodTransfer;
use App\Service\Intl\CurrencyProvider;
use Doctrine\ORM\EntityManagerInterface;

class TelegramBotPaymentMethodCreator
{
    public function __construct(
        private readonly CurrencyProvider $currencyProvider,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @param TelegramBotPaymentMethodTransfer $paymentMethodTransfer
     * @return TelegramBotPaymentMethod
     * @throws CurrencyNotFoundException
     */
    public function createTelegramPaymentMethod(TelegramBotPaymentMethodTransfer $paymentMethodTransfer): TelegramBotPaymentMethod
    {
        $currencyCodes = $paymentMethodTransfer->getCurrencies();
        foreach ($currencyCodes as $currencyCode) {
            if (!$this->currencyProvider->hasCurrency($currencyCode)) {
                throw new CurrencyNotFoundException($currencyCode);
            }
        }

        $paymentMethod = new TelegramBotPaymentMethod(
            $paymentMethodTransfer->getBot(),
            $paymentMethodTransfer->getName(),
            $paymentMethodTransfer->getToken(),
            $currencyCodes,
        );
        $this->entityManager->persist($paymentMethod);

        return $paymentMethod;
    }
}