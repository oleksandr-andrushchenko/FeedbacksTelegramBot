<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use Psr\Log\LoggerInterface;

class TelegramFactory
{
    public function __construct(
        private readonly TelegramClientRegistry $clientRegistry,
        private readonly TelegramRequestChecker $requestChecker,
        private readonly TelegramBotRepository $botRepository,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param string $username
     * @return Telegram
     * @throws TelegramNotFoundException
     */
    public function createTelegram(string $username): Telegram
    {
        $bot = $this->botRepository->findOneByUsername($username);

        if ($bot === null) {
            throw new TelegramNotFoundException($username);
        }

        return new Telegram(
            $bot,
            $this->clientRegistry,
            $this->requestChecker,
            $this->logger,
        );
    }
}
