<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Repository\Telegram\TelegramPaymentRepository;

trait TelegramPaymentRepositoryProviderTrait
{
    public function getTelegramPaymentRepository(): TelegramPaymentRepository
    {
        return static::getContainer()->get('app.repository.telegram_payment');
    }
}