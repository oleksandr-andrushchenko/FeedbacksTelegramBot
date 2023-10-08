<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use Longman\TelegramBot\Entities\Update;

class TelegramBotInputProvider
{
    public function getTelegramInputByUpdate(Update $update): ?string
    {
        $messageText = $update->getMessage()?->getText();

        if ($messageText === null) {
            return $update->getCallbackQuery()?->getData();
        }

        return $messageText;
    }
}