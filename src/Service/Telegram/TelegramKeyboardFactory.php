<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

class TelegramKeyboardFactory
{
    public function __construct(
        protected readonly TelegramTranslator $translator,
    )
    {
    }

    public function createTelegramKeyboard(...$buttons): Keyboard
    {
        return new Keyboard(
            [
                'keyboard' => array_map(fn ($button) => [$button], $buttons),
                'is_persistent' => true,
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
                'selective' => true,
            ]
        );
    }

    public function createTelegramButton(?string $languageCode, string $transId, array $transParameters = []): KeyboardButton
    {
        return new KeyboardButton($this->translator->trans($transId, $transParameters, locale: $languageCode));
    }
}
