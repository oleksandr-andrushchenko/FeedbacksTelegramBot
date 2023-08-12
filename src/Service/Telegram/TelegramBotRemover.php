<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use Doctrine\ORM\EntityManagerInterface;

class TelegramBotRemover
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function removeTelegramBot(TelegramBot $bot): void
    {
        $this->entityManager->remove($bot);
    }
}