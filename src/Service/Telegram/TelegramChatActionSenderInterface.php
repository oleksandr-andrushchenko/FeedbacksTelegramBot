<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\ServerResponse;

interface TelegramChatActionSenderInterface
{
    public function sendChatAction(Telegram $telegram, int $chatId, string $action): ServerResponse;
}