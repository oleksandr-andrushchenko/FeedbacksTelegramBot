<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

interface TelegramMessageSenderInterface
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
    ): ServerResponse;
}