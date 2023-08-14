<?php

declare(strict_types=1);

namespace App\Service\Telegram\Payment;

use App\Entity\Telegram\TelegramPaymentMethod;

class TelegramPaymentMethodInfoProvider
{
    public function getTelegramPaymentInfo(TelegramPaymentMethod $paymentMethod): array
    {
        return [
            'bot' => $paymentMethod->getBot()->getUsername(),
            'name' => $paymentMethod->getName()->name,
            'currencies' => join(', ', $paymentMethod->getCurrencyCodes()),
        ];
    }
}