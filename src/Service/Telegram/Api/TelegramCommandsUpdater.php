<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Command\TelegramCommand;
use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramMyCommands;
use App\Service\Telegram\TelegramMyCommandsProvider;
use App\Service\Telegram\TelegramRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramCommandsUpdater
{
    public function __construct(
        private readonly TelegramRegistry $registry,
        private readonly TranslatorInterface $translator,
        private readonly TelegramMyCommandsProvider $telegramMyCommandsProvider,
        private ?array $myCommands = null,
    )
    {
        $this->myCommands = null;
    }

    /**
     * @param TelegramBot $bot
     * @return void
     */
    public function updateTelegramCommands(TelegramBot $bot): void
    {
        $telegram = $this->registry->getTelegram($bot);
        $this->myCommands = [];

        foreach ($this->telegramMyCommandsProvider->getTelegramMyCommands($telegram) as $myCommands) {
            $data = [
                'scope' => $myCommands->getScope()->jsonSerialize(),
                'commands' => array_map(
                    fn (TelegramCommand $command) => [
                        'command' => $command->getName(),
                        'description' => $this->getDescription($telegram, $command, $myCommands),
                    ],
                    $myCommands->getCommands()
                ),
            ];

            $this->myCommands[] = $myCommands;

            $telegram->setMyCommands($data);
        }

        $bot->setCommandsSet(true);
    }

    /**
     * @return array|null|TelegramMyCommands[]
     */
    public function getMyCommands(): ?array
    {
        return $this->myCommands;
    }

    private function getDescription(
        Telegram $telegram,
        TelegramCommand $command,
        TelegramMyCommands $myCommands
    ): string
    {
        $domain = sprintf('%s.tg.command', $telegram->getBot()->getGroup()->name);
        $locale = $myCommands->getLocaleCode();

        $icon = $this->translator->trans($command->getKey(), domain: $domain, locale: $locale);
        $name = $this->translator->trans($command->getKey(), domain: $domain, locale: $locale);

        return sprintf('%s %s', $icon, $name);
    }
}