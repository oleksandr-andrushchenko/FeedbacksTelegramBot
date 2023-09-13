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
        $paymentMethods = $this->paymentMethodRepository->findActiveByBot($bot);
        $paymentMethodNames = array_map(
            fn (TelegramPaymentMethod $paymentMethod) => sprintf(
                '%s (%s)',
                ucwords($paymentMethod->getName()->name),
                join(', ', $paymentMethod->getCurrencyCodes())
            ),
            $paymentMethods
        );

        $flag = fn ($label, $value) => str_pad($label . ':', 10) . ($value ? 'Yes' : 'No');

        return [
            'group' => $bot->getGroup()->name,
            'name' => $bot->getName(),
            'username' => $bot->getUsername(),
            'channel / group' => join(' / ', [
                $bot->getChannelUsername() === null ? '-' : $bot->getChannelUsername(),
                $bot->getGroupUsername() === null ? '-' : $bot->getGroupUsername(),
            ]),
            'country' => $bot->getCountryCode(),
            'locale' => $bot->getLocaleCode(),
            'payment_methods' => count($paymentMethodNames) === 0 ? 'N/A' : join(', ', $paymentMethodNames),
            'flags' => join("\n", [
                $flag('texts', $bot->textsSet()),
                $flag('webhook', $bot->webhookSet()),
                $flag('commands', $bot->commandsSet()),
                $flag('updates', $bot->checkUpdates()),
                $flag('requests', $bot->checkRequests()),
                $flag('payments', $bot->acceptPayments()),
            ]),
            'admin_ids' => count($bot->getAdminIds()) === 0 ? 'N/A' : join(', ', $bot->getAdminIds()),
            'admin_only' => $bot->adminOnly() ? 'Yes' : 'No',
            'deleted_at' => $bot->getDeletedAt() === null ? '-' : $bot->getDeletedAt()->format('Y-m-d H:i'),
        ];
    }
}