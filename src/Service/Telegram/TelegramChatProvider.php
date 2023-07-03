<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\Entity;
use Longman\TelegramBot\Entities\Update;

class TelegramChatProvider
{
    public function getTelegramChatHolderByUpdate(Update $update): ?Entity
    {
        $updateMethods = [
            'getMessage',
            'getEditedMessage',
            'getMyChatMember',
            'getChatJoinRequest',
            'getChannelPost',
            'getSenderChat',
            'getSenderChat',
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

    public function getTelegramChatByUpdate(Update $update): ?Chat
    {
        return $this->getTelegramChatHolderByUpdate($update)?->getChat();

    }
}