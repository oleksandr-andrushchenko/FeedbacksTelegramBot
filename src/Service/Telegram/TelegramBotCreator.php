<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Exception\Intl\CountryNotFoundException;
use App\Object\Telegram\TelegramBotTransfer;
use App\Service\Intl\CountryProvider;
use Doctrine\ORM\EntityManagerInterface;

class TelegramBotCreator
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @param TelegramBotTransfer $botTransfer
     * @return TelegramBot
     * @throws CountryNotFoundException
     */
    public function createTelegramBot(TelegramBotTransfer $botTransfer): TelegramBot
    {
        $countryCode = $botTransfer->getCountryCode();
        if (!$this->countryProvider->hasCountry($countryCode)) {
            throw new CountryNotFoundException($countryCode);
        }

        $bot = new TelegramBot(
            $botTransfer->getUsername(),
            $botTransfer->getToken(),
            $countryCode,
            $botTransfer->getGroup(),
            $botTransfer->getPrimaryBot()
        );
        $this->entityManager->persist($bot);

        return $bot;
    }
}