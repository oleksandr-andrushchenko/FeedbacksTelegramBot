<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\Update;

class TelegramInputProvider
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