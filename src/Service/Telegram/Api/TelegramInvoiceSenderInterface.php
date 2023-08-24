<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Entity\Telegram\TelegramInvoicePhoto;
use App\Exception\Telegram\Api\InvalidCurrencyTelegramException;
use App\Service\Telegram\Telegram;
use Longman\TelegramBot\Entities\ServerResponse;

interface TelegramInvoiceSenderInterface
{
    /**
     * @param Telegram $telegram
     * @param int $chatId
     * @param string $title
     * @param string $description
     * @param string $payload
     * @param string $providerToken
     * @param string $currency
     * @param array $prices
     * @param TelegramInvoicePhoto|null $photo
     * @param bool $needPhoneNumber
     * @param bool $sendPhoneNumberToProvider
     * @param bool $protectContent
     * @return ServerResponse
     * @throws InvalidCurrencyTelegramException
     */
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
    ): ServerResponse;
}