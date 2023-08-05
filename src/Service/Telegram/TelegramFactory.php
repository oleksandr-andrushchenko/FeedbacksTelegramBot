<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Enum\Telegram\TelegramName;
use App\Exception\Telegram\TelegramException;
use Psr\Log\LoggerInterface;

class TelegramFactory
{
    public function __construct(
        private readonly array $options,
        private readonly TelegramOptionsFactory $optionsFactory,
        private readonly TelegramClientRegistry $clientRegistry,
        private readonly TelegramRequestChecker $requestChecker,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param TelegramName $telegramName
     * @return Telegram
     * @throws TelegramException
     */
    public function createTelegram(TelegramName $telegramName): Telegram
    {
        if (!isset($this->options[$telegramName->name])) {
            throw new TelegramException('Invalid telegram name provided');
        }

        return new Telegram(
            $telegramName,
            $this->optionsFactory->createTelegramOptions($this->options[$telegramName->name], $telegramName->name),
            $this->clientRegistry,
            $this->requestChecker,
            $this->logger,
        );
    }
}
