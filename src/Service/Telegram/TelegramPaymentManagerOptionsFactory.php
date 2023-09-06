<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramPaymentManagerOptions;

class TelegramPaymentManagerOptionsFactory
{
    public function __invoke(array $options): TelegramPaymentManagerOptions
    {
        return new TelegramPaymentManagerOptions(
            $options['log_activities'],
        );
    }
}