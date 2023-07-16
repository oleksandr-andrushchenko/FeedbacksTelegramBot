<?php

declare(strict_types=1);

namespace App\Service\Telegram;

class TelegramCommandFinder
{
    /**
     * @param string|null $commandName
     * @param iterable|TelegramCommandInterface $commands
     * @return TelegramCommandInterface|null
     */
    public function findBeforeConversationCommand(?string $commandName, iterable $commands): ?TelegramCommandInterface
    {
        if ($commandName === null) {
            return null;
        }

        foreach ($commands as $command) {
            if ($commandName !== $command->getName()) {
                continue;
            }

            if (!$command->getBeforeConversations()) {
                continue;
            }

            return $command;
        }

        return null;
    }

    /**
     * @param string|null $commandName
     * @param iterable|TelegramCommandInterface $commands
     * @return TelegramCommandInterface|null
     */
    public function findCommand(?string $commandName, iterable $commands): ?TelegramCommandInterface
    {
        if ($commandName === null) {
            return null;
        }

        foreach ($commands as $command) {
            if ($commandName !== $command->getName()) {
                continue;
            }

            return $command;
        }

        return null;
    }

    /**
     * @param iterable|TelegramCommandInterface $commands
     * @return TelegramCommandInterface|null
     */
    public function findFallbackCommand(iterable $commands): ?TelegramCommandInterface
    {
        foreach ($commands as $command) {
            if ($command instanceof FallbackTelegramCommand) {
                return $command;
            }
        }

        return null;
    }
}