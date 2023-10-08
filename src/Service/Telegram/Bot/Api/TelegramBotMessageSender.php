<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotRegistry;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

class TelegramBotMessageSender implements TelegramBotMessageSenderInterface
{
    public function __construct(
        private readonly TelegramBotRegistry $registry,
    )
    {
    }

    public function sendTelegramMessage(
        TelegramBot $botEntity,
        string|int $chatId,
        string $text,
        Keyboard $keyboard = null,
        string $parseMode = 'HTML',
        int $replyToMessageId = null,
        bool $protectContent = null,
        bool $disableWebPagePreview = true,
        bool $keepKeyboard = false
    ): ServerResponse
    {
        $bot = $this->registry->getTelegramBot($botEntity);

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

        if ($keyboard === null) {
            if (!$keepKeyboard) {
                $data['reply_markup'] = Keyboard::remove();
            }
        } else {
            $data['reply_markup'] = $keyboard;
        }

        if ($protectContent !== null) {
            $data['protect_content'] = $protectContent;
        }

        if ($disableWebPagePreview !== null) {
            $data['disable_web_page_preview'] = $disableWebPagePreview;
        }

        return $bot->sendMessage($data);
    }
}