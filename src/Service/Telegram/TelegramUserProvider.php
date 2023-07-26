<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\Entity;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\User;

class TelegramUserProvider
{
    public function getTelegramFromHolderByUpdate(Update $update): ?Entity
    {
        $updateMethods = [
            'getMessage',
            'getEditedMessage',
            'getChannelPost',
            'getEditedChannelPost',
            'getInlineQuery',
            'getChosenInlineResult',
            'getCallbackQuery',
            'getPreCheckoutQuery',
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

    public function getTelegramUserByUpdate(Update $update): ?User
    {
        return $this->getTelegramFromHolderByUpdate($update)?->getFrom();

    }
}