<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Service\Telegram\Telegram;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

class TelegramMessageSender implements TelegramMessageSenderInterface
{
    public function sendTelegramMessage(
        Telegram $telegram,
        int $chatId,
        string $text,
        Keyboard $keyboard = null,
        string $parseMode = null,
        int $replyToMessageId = null,
        bool $protectContent = null,
        bool $disableWebPagePreview = null
    ): ServerResponse
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyToMessageId !== null) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }

        if ($parseMode !== null) {
            $data['parse_mode'] = $parseMode;
        }

        $data['reply_markup'] = $keyboard ?? Keyboard::remove();

        if ($protectContent !== null) {
            $data['protect_content'] = $protectContent;
        }

        if ($disableWebPagePreview !== null) {
            $data['disable_web_page_preview'] = $disableWebPagePreview;
        }

        return $telegram->sendMessage($data);
    }
}