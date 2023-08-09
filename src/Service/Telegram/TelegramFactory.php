<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Exception\Telegram\TelegramOptionsNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use Psr\Log\LoggerInterface;

class TelegramFactory
{
    public function __construct(
        private readonly array $options,
        private readonly TelegramOptionsFactory $optionsFactory,
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
     * @throws TelegramOptionsNotFoundException
     */
    public function createTelegram(string $username): Telegram
    {
        $bot = $this->botRepository->findOneByUsername($username);

        if ($bot === null) {
            throw new TelegramNotFoundException($username);
        }

        if (!array_key_exists($bot->getGroup()->name, $this->options)) {
            throw new TelegramOptionsNotFoundException($username);
        }

        $groupOptions = $this->options[$bot->getGroup()->name];

        return new Telegram(
            $bot,
            $this->optionsFactory->createTelegramOptions($groupOptions),
            $this->clientRegistry,
            $this->requestChecker,
            $this->logger,
        );
    }
}
