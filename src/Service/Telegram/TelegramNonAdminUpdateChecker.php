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

        $isAdmin = in_array($currentUser->getId(), $telegram->getOptions()->getAdminIds(), true);

        if ($isAdmin) {
            return false;
        }

        return true;
    }
}