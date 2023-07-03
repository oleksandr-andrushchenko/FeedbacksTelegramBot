<?php

declare(strict_types=1);

namespace App\Service\Telegram;

class TelegramDescriptionsUpdater
{
    public function __construct(
        private readonly TelegramTranslator $telegramTranslator,
        private ?array $myNames = null,
        private ?array $myDescriptions = null,
        private ?array $myShortDescriptions = null,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @return void
     */
    public function updateTelegramDescriptions(Telegram $telegram): void
    {
        $this->myNames = [];
        $this->myDescriptions = [];
        $this->myShortDescriptions = [];

        foreach ($telegram->getOptions()->getLanguageCodes() as $languageCode) {
            $name = $this->telegramTranslator->transTelegram($languageCode, 'name');
            $this->myNames[] = $name;
            $telegram->setMyName([
                'name' => $name,
                'language_code' => $languageCode,
            ]);
            $description = $this->telegramTranslator->transTelegram($languageCode, 'description');
            $this->myDescriptions[] = $description;
            $telegram->setMyDescription([
                'description' => $description,
                'language_code' => $languageCode,
            ]);
            $shortDescription = $this->telegramTranslator->transTelegram($languageCode, 'short_description');
            $this->myShortDescriptions[] = $shortDescription;
            $telegram->setMyShortDescription([
                'short_description' => $shortDescription,
                'language_code' => $languageCode,
            ]);
        }
    }

    /**
     * @return array|null
     */
    public function getMyNames(): ?array
    {
        return $this->myNames;
    }

    /**
     * @return array|null
     */
    public function getMyDescriptions(): ?array
    {
        return $this->myDescriptions;
    }

    /**
     * @return array|null
     */
    public function getMyShortDescriptions(): ?array
    {
        return $this->myShortDescriptions;
    }
}