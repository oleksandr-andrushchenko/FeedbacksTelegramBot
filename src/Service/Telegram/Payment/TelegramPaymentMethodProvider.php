<?php

declare(strict_types=1);

namespace App\Service\Telegram\Payment;

use App\Enum\Telegram\TelegramPaymentMethodName;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramPaymentMethodProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getPaymentMethodName(TelegramPaymentMethodName $paymentMethod, string $localeCode = null): string
    {
        return $this->translator->trans($paymentMethod->name, domain: 'tg.payment_methods', locale: $localeCode);
    }
}