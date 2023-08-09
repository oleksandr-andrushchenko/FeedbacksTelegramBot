<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramOptions;

class TelegramOptionsFactory
{
    /**
     * @param array $groupOptions
     * @return TelegramOptions
     */
    public function createTelegramOptions(array $groupOptions): TelegramOptions
    {
        return new TelegramOptions(
            $groupOptions['locales'],
            $groupOptions['admin_id'],
        );
    }
}
