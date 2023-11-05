<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBotCommand;
use App\Entity\Telegram\TelegramBotMyCommands;
use App\Service\Telegram\Bot\Group\TelegramBotGroupRegistry;
use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScopeChat;
use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScopeDefault;

class TelegramBotMyCommandsProvider
{
    public function __construct(
        private readonly TelegramBotGroupRegistry $telegramBotGroupRegistry,
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @return iterable|TelegramBotMyCommands[]
     */
    public function getTelegramMyCommands(TelegramBot $bot): iterable
    {
        $group = $this->telegramBotGroupRegistry->getTelegramGroup($bot->getEntity()->getGroup());

        $realCommands = array_values(
            array_filter($group->getTelegramCommands($bot), static fn ($command): bool => $command instanceof TelegramBotCommand)
        );

        $adminChatScopeCommands = array_values(
            array_filter($realCommands, static fn (TelegramBotCommand $command): bool => $command->isMenu())
        );

        $defaultScopeCommands = array_values(
            array_filter($adminChatScopeCommands, static fn ($command): bool => true)
        );

        $localeCode = $bot->getEntity()->getLocaleCode();

        $defaultScope = new BotCommandScopeDefault();
        yield new TelegramBotMyCommands($defaultScopeCommands, $defaultScope, $localeCode);

        foreach ($bot->getEntity()->getAdminIds() as $adminId) {
            $adminChatScope = new BotCommandScopeChat(['chat_id' => $adminId]);

            yield new TelegramBotMyCommands($adminChatScopeCommands, $adminChatScope, $localeCode);
        }
    }
}