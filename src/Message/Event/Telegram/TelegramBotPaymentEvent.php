<?php

declare(strict_types=1);

namespace App\Message\Event\Telegram;

use App\Entity\Telegram\TelegramBotPayment;
use LogicException;

abstract readonly class TelegramBotPaymentEvent
{
    private ?string $paymentId;

    public function __construct(
        private ?TelegramBotPayment $payment = null,
        ?string $paymentId = null,
    )
    {
        if ($paymentId === null) {
            if ($this->payment === null) {
                throw new LogicException('Either payment id or payment should be passed`');
            }

            $this->paymentId = $this->payment->getId();
        } else {
            $this->paymentId = $paymentId;
        }
    }

    public function getPayment(): ?TelegramBotPayment
    {
        return $this->payment;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function __sleep(): array
    {
        return [
            'paymentId',
        ];
    }
}
