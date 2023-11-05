<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Transfer\Telegram\TelegramBotTransfer;
use Doctrine\ORM\EntityManagerInterface;

class TelegramBotCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramBotValidator $telegramBotValidator,
    )
    {
    }

    /**
     * @param TelegramBotTransfer $botTransfer
     * @return TelegramBot
     */
    public function createTelegramBot(TelegramBotTransfer $botTransfer): TelegramBot
    {
        $bot = new TelegramBot(
            $botTransfer->getUsername(),
            $botTransfer->getGroup(),
            $botTransfer->getName(),
            $botTransfer->getToken(),
            $botTransfer->getCountry()->getCode(),
            $botTransfer->getLocale()?->getCode() ?? $botTransfer->getCountry()->getLocaleCodes()[0],
            checkUpdates: $botTransfer->checkUpdates() ?? true,
            checkRequests: $botTransfer->checkRequests() ?? true,
            acceptPayments: $botTransfer->acceptPayments() ?? false,
            adminIds: $botTransfer->getAdminIds() ?? [],
            adminOnly: $botTransfer->adminOnly() ?? true,
            primary: $botTransfer->primary() ?? true,
        );

        $this->telegramBotValidator->validateTelegramBot($bot);

        $this->entityManager->persist($bot);

        return $bot;
    }
}