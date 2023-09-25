<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Object\Telegram\TelegramBotTransfer;
use Doctrine\ORM\EntityManagerInterface;

class TelegramBotCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramBotValidator $validator,
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
            $botTransfer->localePassed() ? $botTransfer->getLocale()->getCode() : $botTransfer->getCountry()->getLocaleCodes()[0],
            channelUsername: $botTransfer->getChannelUsername(),
            groupUsername: $botTransfer->getGroupUsername(),
            checkUpdates: $botTransfer->checkUpdates(),
            checkRequests: $botTransfer->checkRequests(),
            acceptPayments: $botTransfer->acceptPayments(),
            adminIds: $botTransfer->getAdminIds(),
            adminOnly: $botTransfer->adminOnly(),
            singleChannel: $botTransfer->singleChannel(),
        );

        $this->validator->validateTelegramBot($bot);

        $this->entityManager->persist($bot);

        return $bot;
    }
}