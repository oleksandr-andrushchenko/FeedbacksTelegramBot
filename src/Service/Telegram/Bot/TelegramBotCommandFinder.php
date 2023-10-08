<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBotCommand;
use App\Entity\Telegram\TelegramBotCommandInterface;
use App\Entity\Telegram\ErrorTelegramBotCommand;
use App\Entity\Telegram\FallbackTelegramBotCommand;

class TelegramBotCommandFinder
{
    /**
     * @param string|null $commandName
     * @param TelegramBotCommandInterface[] $commands
     * @return TelegramBotCommand|null
     */
    public function findBeforeConversationCommand(?string $commandName, array $commands): ?TelegramBotCommand
    {
        if ($commandName === null) {
            return null;
        }

        foreach ($commands as $command) {
            if (!$command instanceof TelegramBotCommand) {
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
     * @param TelegramBotCommandInterface[] $commands
     * @return TelegramBotCommand|null
     */
    public function findCommand(?string $commandName, array $commands): ?TelegramBotCommand
    {
        if ($commandName === null) {
            return null;
        }

        foreach ($commands as $command) {
            if (!$command instanceof TelegramBotCommand) {
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
     * @param TelegramBotCommandInterface[] $commands
     * @return FallbackTelegramBotCommand|null
     */
    public function findFallbackCommand(array $commands): ?FallbackTelegramBotCommand
    {
        foreach ($commands as $command) {
            if ($command instanceof FallbackTelegramBotCommand) {
                return $command;
            }
        }

        return null;
    }

    /**
     * @param TelegramBotCommandInterface[] $commands
     * @return ErrorTelegramBotCommand|null
     */
    public function findErrorCommand(array $commands): ?ErrorTelegramBotCommand
    {
        foreach ($commands as $command) {
            if ($command instanceof ErrorTelegramBotCommand) {
                return $command;
            }
        }

        return null;
    }
}