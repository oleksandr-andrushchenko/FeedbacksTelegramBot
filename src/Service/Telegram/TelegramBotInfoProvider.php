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
            'channel' => $bot->getChannelUsername() ?: 'N/A',
            'comments' => $bot->getGroupUsername() ?: 'N/A',
            'country' => $bot->getCountryCode(),
            'region1' => $bot->getRegion1() ?: 'N/A',
            'region2' => $bot->getRegion2() ?: 'N/A',
            'locality' => $bot->getLocality() ?: 'N/A',
            'locale' => $bot->getLocaleCode(),
            'texts' => $bot->textsSet() ? 'Yes' : 'No',
            'payment_methods' => count($paymentMethodNames) === 0 ? 'N/A' : join(', ', $paymentMethodNames),
            'payments' => $bot->acceptPayments() ? 'Yes' : 'No',
            'webhook' => $bot->webhookSet() ? 'Yes' : 'No',
            'commands' => $bot->commandsSet() ? 'Yes' : 'No',
            'updates' => $bot->checkUpdates() ? 'Yes' : 'No',
            'requests' => $bot->checkRequests() ? 'Yes' : 'No',
            'admin_ids' => count($bot->getAdminIds()) === 0 ? 'N/A' : join(', ', $bot->getAdminIds()),
            'admin_only' => $bot->adminOnly() ? 'Yes' : 'No',
            'deleted_at' => $bot->getDeletedAt() === null ? 'N/A' : $bot->getDeletedAt()->format('Y-m-d H:i'),
        ];

        if (!$full) {
            unset(
                $info['group'],
                $info['payment_methods'],
                $info['commands'],
                $info['updates'],
                $info['requests'],
                $info['admin_ids'],
            );
        }

        return $info;
    }
}