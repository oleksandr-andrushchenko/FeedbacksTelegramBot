<?php

declare(strict_types=1);

namespace App\Service\Telegram;

class TelegramCommandsUpdater
{
    public function __construct(
        private readonly TelegramTranslator $telegramTranslator,
        private readonly TelegramMyCommandsProvider $telegramMyCommandsProvider,
        private ?array $myCommands = null,
    )
    {
        $this->myCommands = null;
    }

    /**
     * @param Telegram $telegram
     * @return void
     */
    public function updateTelegramCommands(Telegram $telegram): void
    {
        $this->myCommands = [];

        foreach ($this->telegramMyCommandsProvider->getTelegramMyCommands($telegram) as $myCommands) {
            if (!in_array($myCommands->getLanguageCode(), $telegram->getOptions()->getLanguageCodes(), true)) {
                continue;
            }

            $data = [];

            $data['language_code'] = $myCommands->getLanguageCode();
            $data['scope'] = $myCommands->getScope()->jsonSerialize();
            $data['commands'] = array_map(
                fn (TelegramCommandInterface $command) => [
                    'command' => $command->getName(),
                    'description' => $this->telegramTranslator->transTelegram(
                        $myCommands->getLanguageCode(),
                        sprintf('%s.description.%s', $telegram->getName()->name, $command->getKey())
                    ),
                ],
                $myCommands->getCommands()
            );

            $this->myCommands[] = $myCommands;

            $telegram->setMyCommands($data);
        }
    }

    /**
     * @return array|null|TelegramMyCommands[]
     */
    public function getMyCommands(): ?array
    {
        return $this->myCommands;
    }
}