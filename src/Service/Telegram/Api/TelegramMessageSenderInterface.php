<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Service\Telegram\Telegram;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

interface TelegramMessageSenderInterface
{
    public function sendTelegramMessage(
        Telegram $telegram,
        int $chatId,
        string $text,
        Keyboard $keyboard = null,
        string $parseMode = 'HTML',
        int $replyToMessageId = null,
        bool $protectContent = null,
        bool $disableWebPagePreview = true
    ): ServerResponse;
}