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

    public function getTelegramBotInfo(TelegramBot $bot, bool $full = true): array
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

        $info = [
            'group' => $bot->getGroup()->name,
            'name' => $bot->getName(),
            'username' => $bot->getUsername(),
            'country' => $bot->getCountryCode(),
            'locale' => $bot->getLocaleCode(),
            'primary' => $bot->primary() ? 'Yes' : 'No',
            'texts' => $bot->textsSet() ? 'Yes' : 'No',
            'payment_methods' => count($paymentMethodNames) === 0 ? 'N/A' : join(', ', $paymentMethodNames),
            'payments' => $bot->acceptPayments() ? 'Yes' : 'No',
            'webhook' => $bot->webhookSet() ? 'Yes' : 'No',
            'commands' => $bot->commandsSet() ? 'Yes' : 'No',
            'updates' => $bot->checkUpdates() ? 'Yes' : 'No',
            'requests' => $bot->checkRequests() ? 'Yes' : 'No',
            'admin_ids' => count($bot->getAdminIds()) === 0 ? 'N/A' : join(', ', $bot->getAdminIds()),
            'admin_only' => $bot->adminOnly() ? 'Yes' : 'No',
            'created_at' => $bot->getCreatedAt()->format('Y-m-d H:i'),
            'updated_at' => $bot->getUpdatedAt() === null ? 'N/A' : $bot->getUpdatedAt()->format('Y-m-d H:i'),
            'deleted_at' => $bot->getDeletedAt() === null ? 'N/A' : $bot->getDeletedAt()->format('Y-m-d H:i'),
        ];

        if (!$full) {
            unset(
                $info['payment_methods'],
                $info['commands'],
                $info['created_at'],
                $info['updated_at'],
            );
        }

        return $info;
    }
}