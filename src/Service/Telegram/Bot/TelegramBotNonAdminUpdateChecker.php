<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

class TelegramBotNonAdminUpdateChecker
{
    public function __construct(
        private readonly TelegramBotUserProvider $userProvider,
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @return bool
     */
    public function checkNonAdminUpdate(TelegramBot $bot): bool
    {
        if (!$bot->getEntity()->adminOnly()) {
            return false;
        }

        $currentUser = $this->userProvider->getTelegramUserByUpdate($bot->getUpdate());

        if (in_array($currentUser?->getId(), $bot->getEntity()->getAdminIds(), true)) {
            return false;
        }

        return true;
    }
}