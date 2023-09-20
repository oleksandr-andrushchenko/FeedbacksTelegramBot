<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use Psr\Log\LoggerInterface;

class TelegramFactory
{
    public function __construct(
        private readonly TelegramClientRegistry $clientRegistry,
        private readonly TelegramRequestChecker $requestChecker,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function createTelegram(TelegramBot $bot): Telegram
    {
        return new Telegram(
            $bot,
            $this->clientRegistry,
            $this->requestChecker,
            $this->logger,
        );
    }
}
