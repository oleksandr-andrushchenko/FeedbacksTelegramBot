<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Repository\Telegram\TelegramBotRepository;
use RuntimeException;

class BetterMatchTelegramBotProvider
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
    )
    {
    }

    public function getBetterMatchTelegramBot(MessengerUser $messengerUser, TelegramBot $currentBot): ?TelegramBot
    {
        $user = $messengerUser->getUser();
        $countyCode = $user->getCountryCode();
        $localeCode = $user->getLocaleCode();

        if ($currentBot->getCountryCode() === $countyCode && $currentBot->getLocaleCode() === $localeCode) {
            return null;
        }

        $bots = $this->repository->findByGroup($currentBot->getGroup());

        if (count($bots) === 0) {
            throw new RuntimeException(sprintf('No telegram bots has been found for "%s" group', $currentBot->getGroup()->name));
        }

        $bots = array_filter($bots, fn (TelegramBot $bot) => $bot->getId() !== $currentBot->getId());
        $bots = array_filter($bots, fn (TelegramBot $bot) => $bot->getCountryCode() === $countyCode);

        if (count($bots) === 0) {
            return null;
        }

        reset($bots);
        $countryBot = current($bots);

        $bots = array_filter($bots, fn (TelegramBot $bot) => $bot->getLocaleCode() === $localeCode);

        if (count($bots) === 0) {
            if ($countryBot->getCountryCode() === $currentBot->getCountryCode()) {
                return null;
            }

            return $countryBot;
        }

        reset($bots);

        return current($bots);
    }
}