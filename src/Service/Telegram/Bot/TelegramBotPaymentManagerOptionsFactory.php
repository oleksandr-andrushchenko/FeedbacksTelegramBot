<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPaymentManagerOptions;

class TelegramBotPaymentManagerOptionsFactory
{
    public function __invoke(array $options): TelegramBotPaymentManagerOptions
    {
        return new TelegramBotPaymentManagerOptions(
            $options['log_activities'],
        );
    }
}