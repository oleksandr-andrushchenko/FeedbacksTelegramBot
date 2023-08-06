<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Exception\Telegram\TelegramNotFoundException;
use App\Exception\Telegram\TelegramOptionsNotFoundException;
use Psr\Log\LoggerInterface;

class TelegramFactory
{
    public function __construct(
        private readonly array $options,
        private readonly TelegramGroupOptionsFactory $groupOptionsFactory,
        private readonly TelegramOptionsFactory $optionsFactory,
        private readonly TelegramClientRegistry $clientRegistry,
        private readonly TelegramRequestChecker $requestChecker,
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
        foreach (TelegramGroup::cases() as $group) {
            $options = null;
            foreach ($this->options as $opts) {
                if ($opts['key'] === $group->name) {
                    $options = $opts;
                    break;
                }
            }

            if ($options === null) {
                continue;
            }

            $groupOptions = $this->groupOptionsFactory->createTelegramGroupOptions($options);

            if ($groupOptions->hasBot($username)) {
                return new Telegram(
                    $group,
                    $this->optionsFactory->createTelegramOptions($username, $options),
                    $this->clientRegistry,
                    $this->requestChecker,
                    $this->logger,
                );
            }
        }

        throw new TelegramNotFoundException();
    }
}
