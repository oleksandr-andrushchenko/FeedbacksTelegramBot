<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Intl\CountryProvider;
use LogicException;

class TelegramBotValidator
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @return void
     * @throws LogicException
     */
    public function validateTelegramBot(TelegramBot $bot): void
    {
        $group = $bot->getGroup();
        $countryCode = $bot->getCountryCode();
        $localeCode = $bot->getLocaleCode();

        $existing = $this->repository->findOneByGroupCountryAndLocale($group, $countryCode, $localeCode);

        if ($existing !== null && $existing->getId() !== $bot->getId()) {
            throw new LogicException(sprintf('"%s" Telegram bot already has the same group, country and locale', $existing->getUsername()));
        }

        $country = $this->countryProvider->getCountry($countryCode);

        if (!in_array($localeCode, $country->getLocaleCodes(), true)) {
            throw new LogicException(sprintf('"%s" locale not belongs to "%s" country', $localeCode, $countryCode));
        }
    }
}