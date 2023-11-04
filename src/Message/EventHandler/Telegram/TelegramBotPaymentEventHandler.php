<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Telegram;

use App\Message\Event\Telegram\TelegramBotPaymentCreatedEvent;
use App\Message\Event\Telegram\TelegramBotPaymentEvent;
use App\Message\Event\Telegram\TelegramBotPaymentPreCheckoutEvent;
use App\Message\Event\Telegram\TelegramBotPaymentSuccessfulEvent;
use App\Repository\Telegram\Bot\TelegramBotPaymentRepository;
use Psr\Log\LoggerInterface;

class TelegramBotPaymentEventHandler
{
    public function __construct(
        private readonly TelegramBotPaymentRepository $paymentRepository,
        private readonly LoggerInterface $activityLogger,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function invokeCreated(TelegramBotPaymentCreatedEvent $event): void
    {
        $this->invoke($event);
    }

    public function invokePreCheckout(TelegramBotPaymentPreCheckoutEvent $event): void
    {
        $this->invoke($event);
    }

    public function invokeSuccessful(TelegramBotPaymentSuccessfulEvent $event): void
    {
        $this->invoke($event);
    }

    public function invoke(TelegramBotPaymentEvent $event): void
    {
        $payment = $event->getPayment() ?? $this->paymentRepository->find($event->getPaymentId());

        if ($payment === null) {
            $this->logger->warning(sprintf('No telegram bot payment was found in %s for %s id', __CLASS__, $event->getPaymentId()));
            return;
        }

        $this->activityLogger->info($payment);
    }
}