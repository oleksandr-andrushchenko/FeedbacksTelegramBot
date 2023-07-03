<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\ServerResponse;

class TelegramChatActionSender implements TelegramChatActionSenderInterface
{
    public function sendChatAction(Telegram $telegram, int $chatId, string $action): ServerResponse
    {
        $data = [
            'chat_id' => $chatId,
            'action' => $action,
        ];

        return $telegram->sendChatAction($data);
    }
}