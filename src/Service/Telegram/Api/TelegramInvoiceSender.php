<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Entity\Telegram\TelegramInvoicePhoto;
use App\Exception\Telegram\Api\InvalidCurrencyTelegramException;
use App\Exception\Telegram\TelegramException;
use App\Service\Telegram\Telegram;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use Longman\TelegramBot\Entities\ServerResponse;

class TelegramInvoiceSender
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
    ): ServerResponse
    {
        $data = [
            'chat_id' => $chatId,
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
            'provider_token' => $providerToken,
            'currency' => $currency,
            'prices' => array_map(fn (LabeledPrice $price) => $price->jsonSerialize(), $prices),
        ];

        if ($photo !== null) {
            $data['photo_url'] = $photo->url();

            if ($photo->size() !== null) {
                $data['photo_size'] = $photo->size();
            }

            if ($photo->width() !== null) {
                $data['photo_width'] = $photo->width();
            }

            if ($photo->height() !== null) {
                $data['photo_height'] = $photo->height();
            }
        }

        if ($needPhoneNumber !== null) {
            $data['need_phone_number'] = $needPhoneNumber;
        }

        if ($sendPhoneNumberToProvider) {
            $data['send_phone_number_to_provider'] = $sendPhoneNumberToProvider;
        }

        if ($protectContent !== null) {
            $data['protect_content'] = $protectContent;
        }

        try {
            return $telegram->sendInvoice($data);
        } catch (TelegramException $exception) {
            if (str_contains($exception->getMessage(), 'CURRENCY_INVALID')) {
                throw new InvalidCurrencyTelegramException($currency);
            }

            throw $exception;
        }
    }
}