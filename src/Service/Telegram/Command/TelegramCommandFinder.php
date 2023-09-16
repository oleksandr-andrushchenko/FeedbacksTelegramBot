<?php

declare(strict_types=1);

namespace App\Service\Telegram\Command;

class TelegramCommandFinder
{
    /**
     * @param string|null $commandName
     * @param TelegramCommandInterface[] $commands
     * @return TelegramCommand|null
     */
    public function findBeforeConversationCommand(?string $commandName, array $commands): ?TelegramCommand
    {
        if ($commandName === null) {
            return null;
        }

        foreach ($commands as $command) {
            if (!$command instanceof TelegramCommand) {
                continue;
            }
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
     * @param TelegramCommandInterface[] $commands
     * @return TelegramCommand|null
     */
    public function findCommand(?string $commandName, array $commands): ?TelegramCommand
    {
        if ($commandName === null) {
            return null;
        }

        foreach ($commands as $command) {
            if (!$command instanceof TelegramCommand) {
                continue;
            }
            if ($commandName !== $command->getName()) {
                continue;
            }

            return $command;
        }

        return null;
    }

    /**
     * @param TelegramCommandInterface[] $commands
     * @return TelegramFallbackCommand|null
     */
    public function findFallbackCommand(array $commands): ?TelegramFallbackCommand
    {
        foreach ($commands as $command) {
            if ($command instanceof TelegramFallbackCommand) {
                return $command;
            }
        }

        return null;
    }

    /**
     * @param TelegramCommandInterface[] $commands
     * @return TelegramErrorCommand|null
     */
    public function findErrorCommand(array $commands): ?TelegramErrorCommand
    {
        foreach ($commands as $command) {
            if ($command instanceof TelegramErrorCommand) {
                return $command;
            }
        }

        return null;
    }
}