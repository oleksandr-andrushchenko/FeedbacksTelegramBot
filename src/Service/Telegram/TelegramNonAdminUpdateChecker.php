<?php

declare(strict_types=1);

namespace App\Service\Telegram;

class TelegramNonAdminUpdateChecker
{
    public function __construct(
        private readonly TelegramUserProvider $userProvider,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @return bool
     */
    public function checkNonAdminUpdate(Telegram $telegram): bool
    {
        if (!$telegram->getOptions()->processAdminOnly()) {
            return false;
        }

        $currentUser = $this->userProvider->getTelegramUserByUpdate($telegram->getUpdate());

        if ($currentUser->getId() === $telegram->getOptions()->getAdminId()) {
            return false;
        }

        return true;
    }
}