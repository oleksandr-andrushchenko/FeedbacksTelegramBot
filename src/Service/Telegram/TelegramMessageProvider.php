<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\Update;

class TelegramMessageProvider
{
    public function getTelegramMessageByUpdate(Update $update): ?Message
    {
        $updateMethods = [
            'getMessage',
            'getEditedMessage',
            'getChannelPost',
            'getEditedChannelPost',
        ];
        foreach ($updateMethods as $updateMethod) {
            $object = call_user_func([$update, $updateMethod]);

            if ($object === null) {
                continue;
            }

            return $object;
        }

        return null;
    }
}