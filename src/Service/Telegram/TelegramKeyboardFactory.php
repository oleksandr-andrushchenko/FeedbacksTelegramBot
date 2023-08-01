<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

class TelegramKeyboardFactory
{
    public function createTelegramKeyboard(...$buttons): Keyboard
    {
        return new Keyboard(
            [
                'keyboard' => array_map(fn ($button) => is_array($button) ? $button : [$button], $buttons),
                'is_persistent' => true,
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
                'selective' => true,
            ]
        );
    }

    public function createTelegramButton(string $text): KeyboardButton
    {
        return new KeyboardButton($text);
    }
}
