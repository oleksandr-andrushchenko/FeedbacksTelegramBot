<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramPaymentMethod;
use App\Repository\Telegram\TelegramPaymentMethodRepository;

class TelegramBotInfoProvider
{
    public function __construct(
        private readonly TelegramPaymentMethodRepository $paymentMethodRepository,
    )
    {
    }

    public function getTelegramBotInfo(TelegramBot $bot): array
    {
        $paymentMethods = $this->paymentMethodRepository->findByBot($bot);
        $paymentMethodNames = array_map(fn (TelegramPaymentMethod $paymentMethod) => ucwords($paymentMethod->getName()->name), $paymentMethods);

        return [
            'group' => $bot->getGroup()->name,
            'username' => $bot->getUsername(),
            'texts' => $bot->textsSet() ? 'Yes' : 'No',
            'webhook' => $bot->webhookSet() ? 'Yes' : 'No',
            'commands' => $bot->commandsSet() ? 'Yes' : 'No',
            'country' => $bot->getCountryCode(),
            'primary' => $bot->getPrimaryBot() === null ? 'Yes' : sprintf('No (%s)', $bot->getPrimaryBot()->getUsername()),
            'check_updates' => $bot->checkUpdates() ? 'Yes' : 'No',
            'check_requests' => $bot->checkRequests() ? 'Yes' : 'No',
            'accept_payments' => $bot->acceptPayments() ? 'Yes' : 'No',
            'payment_methods' => count($paymentMethodNames) === 0 ? 'N/A' : join(', ', $paymentMethodNames),
            'admin_only' => $bot->adminOnly() ? 'Yes' : 'No',
        ];
    }
}