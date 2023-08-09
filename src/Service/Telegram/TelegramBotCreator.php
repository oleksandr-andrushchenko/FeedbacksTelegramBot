<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Exception\Telegram\TelegramNotFoundException;
use App\Object\Telegram\TelegramBotTransfer;
use App\Repository\Telegram\TelegramBotRepository;
use Doctrine\ORM\EntityManagerInterface;

class TelegramBotCreator
{
    public function __construct(
        private readonly TelegramBotRepository $botRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @param TelegramBotTransfer $botTransfer
     * @return TelegramBot
     * @throws TelegramNotFoundException
     */
    public function createTelegramBot(TelegramBotTransfer $botTransfer): TelegramBot
    {
        if ($botTransfer->getPrimaryBotUsername() === null) {
            $primaryBot = null;
        } else {
            $primaryBot = $this->botRepository->findOneByUsername($botTransfer->getPrimaryBotUsername());

            if ($primaryBot === null) {
                throw new TelegramNotFoundException($botTransfer->getUsername());
            }
        }
        $bot = new TelegramBot(
            $botTransfer->getUsername(),
            $botTransfer->getToken(),
            $botTransfer->getCountryCode(),
            $botTransfer->getLocaleCode(),
            $botTransfer->getGroup(),
            $primaryBot
        );
        $this->entityManager->persist($bot);

        return $bot;
    }
}