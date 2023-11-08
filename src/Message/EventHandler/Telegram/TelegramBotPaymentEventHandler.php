<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Telegram;

use App\Message\Command\NotifyActivityAdminsCommand;
use App\Message\Event\Telegram\TelegramBotPaymentEvent;
use App\Repository\Telegram\Bot\TelegramBotPaymentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TelegramBotPaymentEventHandler
{
    public function __construct(
        private readonly TelegramBotPaymentRepository $telegramBotPaymentRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(TelegramBotPaymentEvent $event): void
    {
        $payment = $event->getPayment() ?? $this->telegramBotPaymentRepository->find($event->getPaymentId());

        if ($payment === null) {
            $this->logger->warning(sprintf('No telegram bot payment was found in %s for %s id', __CLASS__, $event->getPaymentId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyActivityAdminsCommand(entity: $payment));
    }
}