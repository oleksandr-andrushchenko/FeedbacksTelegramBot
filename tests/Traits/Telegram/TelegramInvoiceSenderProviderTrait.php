<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Tests\Fake\Service\Telegram\Api\FakeTelegramInvoiceSender;

trait TelegramInvoiceSenderProviderTrait
{
    public function getTelegramInvoiceSender(): FakeTelegramInvoiceSender
    {
        return static::getContainer()->get('app.telegram_invoice_sender');
    }
}