<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Service\Telegram\Channel\TelegramChannelRegistry;
use App\Service\Telegram\Command\TelegramCommand;
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
        $channel = $this->channelRegistry->getTelegramChannel($telegram->getBot()->getGroup());

        $realCommands = array_values(
            array_filter($channel->getTelegramCommands($telegram), fn ($command) => $command instanceof TelegramCommand)
        );

        $adminChatScopeCommands = array_values(
            array_filter($realCommands, fn (TelegramCommand $command) => $command->isMenu())
        );

        $defaultScopeCommands = array_values(
            array_filter($adminChatScopeCommands, fn ($command) => true)
        );

        $localeCode = $telegram->getBot()->getLocaleCode();

        $defaultScope = new BotCommandScopeDefault();
        yield new TelegramMyCommands($defaultScopeCommands, $defaultScope, $localeCode);

        foreach ($telegram->getBot()->getAdminIds() as $adminId) {
            $adminChatScope = new BotCommandScopeChat(['chat_id' => $adminId]);

            yield new TelegramMyCommands($adminChatScopeCommands, $adminChatScope, $localeCode);
        }
    }
}