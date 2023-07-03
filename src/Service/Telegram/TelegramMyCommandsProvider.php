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
        $channel = $this->channelRegistry->getTelegramChannel($telegram->getName());

        $nonFallbackCommands = array_values(
            array_filter((array) $channel->getTelegramCommands($telegram), fn ($command) => !$command instanceof FallbackTelegramCommand)
        );

        $adminChatScopeCommands = array_values(
            array_filter($nonFallbackCommands, fn ($command) => !$command->getKeyboardOnly())
        );

        $defaultScopeCommands = array_values(
            array_filter($adminChatScopeCommands, fn ($command) => !$command->getAdminOnly())
        );

        $defaultScope = new BotCommandScopeDefault();

        yield new TelegramMyCommands($defaultScopeCommands, $defaultScope, 'uk');
        yield new TelegramMyCommands($defaultScopeCommands, $defaultScope, 'en');
        yield new TelegramMyCommands($defaultScopeCommands, $defaultScope, 'ru');

        foreach ($telegram->getOptions()->getAdminChatIds() as $adminChatId) {
            $adminChatScope = new BotCommandScopeChat(['chat_id' => $adminChatId]);

            yield new TelegramMyCommands($adminChatScopeCommands, $adminChatScope, 'uk');
            yield new TelegramMyCommands($adminChatScopeCommands, $adminChatScope, 'en');
            yield new TelegramMyCommands($adminChatScopeCommands, $adminChatScope, 'ru');
        }
    }
}