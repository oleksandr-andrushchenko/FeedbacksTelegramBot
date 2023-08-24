<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Repository\Telegram\TelegramPaymentMethodRepository;

trait TelegramPaymentMethodRepositoryProviderTrait
{
    public function getTelegramPaymentMethodRepository(): TelegramPaymentMethodRepository
    {
        return static::getContainer()->get('app.repository.telegram_payment_method');
    }
}