<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use DateTimeImmutable;

class TelegramBotRemover
{
    public function removeTelegramBot(TelegramBot $bot): void
    {
        $bot->setDeletedAt(new DateTimeImmutable());
    }
}