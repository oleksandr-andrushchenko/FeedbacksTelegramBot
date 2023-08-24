<?php

declare(strict_types=1);

namespace App\Tests\Fake\Service\Telegram\Api;

use App\Entity\Telegram\TelegramInvoicePhoto;
use App\Service\Telegram\Api\TelegramInvoiceSenderInterface;
use App\Service\Telegram\Telegram;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class FakeTelegramInvoiceSender implements TelegramInvoiceSenderInterface
{
    public function __construct(private array $calls = [])
    {
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function sendInvoice(
        Telegram $telegram,
        int $chatId,
        string $title,
        string $description,
        string $payload,
        string $providerToken,
        string $currency,
        array $prices,
        TelegramInvoicePhoto $photo = null,
        bool $needPhoneNumber = true,
        bool $sendPhoneNumberToProvider = true,
        bool $protectContent = true,
    ): ServerResponse
    {
        $this->calls[] = func_get_args();

        return Request::emptyResponse();
    }
}
