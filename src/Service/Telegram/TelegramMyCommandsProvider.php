<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScopeChat;
use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScopeDefault;

class TelegramMyCommandsProvider
{
    public function __construct(
        private readonly TelegramChannelRegistry $channelRegistry,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @return iterable|TelegramMyCommands[]
     */
    public function getTelegramMyCommands(Telegram $telegram): iterable
    {
        $channel = $this->channelRegistry->getTelegramChannel($telegram->getGroup());

        $realCommands = array_values(
            array_filter($channel->getTelegramCommands($telegram), fn ($command) => $command instanceof TelegramCommand)
        );

        $adminChatScopeCommands = array_values(
            array_filter($realCommands, fn (TelegramCommand $command) => $command->isMenu())
        );

        $defaultScopeCommands = array_values(
            array_filter($adminChatScopeCommands, fn ($command) => true)
        );

        $defaultScope = new BotCommandScopeDefault();

        foreach ($telegram->getOptions()->getLocaleCodes() as $localeCode) {
            yield new TelegramMyCommands($defaultScopeCommands, $defaultScope, $localeCode);
        }

        $adminChatScope = new BotCommandScopeChat(['chat_id' => $telegram->getOptions()->getAdminId()]);

        foreach ($telegram->getOptions()->getLocaleCodes() as $localeCode) {
            yield new TelegramMyCommands($adminChatScopeCommands, $adminChatScope, $localeCode);
        }
    }
}