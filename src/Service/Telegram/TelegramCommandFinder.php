<?php

declare(strict_types=1);

namespace App\Service\Telegram;

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
     * @return FallbackTelegramCommand|null
     */
    public function findFallbackCommand(array $commands): ?FallbackTelegramCommand
    {
        foreach ($commands as $command) {
            if ($command instanceof FallbackTelegramCommand) {
                return $command;
            }
        }

        return null;
    }

    /**
     * @param TelegramCommandInterface[] $commands
     * @return ErrorTelegramCommand|null
     */
    public function findErrorCommand(array $commands): ?ErrorTelegramCommand
    {
        foreach ($commands as $command) {
            if ($command instanceof ErrorTelegramCommand) {
                return $command;
            }
        }

        return null;
    }
}