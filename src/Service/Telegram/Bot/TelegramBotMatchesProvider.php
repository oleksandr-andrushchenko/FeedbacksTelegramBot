<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Bot\TelegramBotRepository;

class TelegramBotMatchesProvider
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
    )
    {
    }

    /**
     * @param User $user
     * @param TelegramBotGroupName $group
     * @return TelegramBot[]
     */
    public function getTelegramBotMatches(User $user, TelegramBotGroupName $group): array
    {
        $bots = $this->repository->findPrimaryByGroup($group);

        if (count($bots) === 0) {
            return [];
        }

        $bots = array_combine(array_map(fn (TelegramBot $bot) => $bot->getId(), $bots), $bots);

        $points = [];

        foreach ($bots as $id => $bot) {
            $points[$id] = $this->calculateTelegramBotPoints($bot, $user);
        }

        $points = array_filter($points);

        if (count($points) === 0) {
            return [];
        }

        asort($points);

        $lastMaxPointsId = array_key_last($points);
        $maxPoints = $points[$lastMaxPointsId];

        $maxPointsIds = array_keys(array_filter($points, fn (int $pts) => $pts === $maxPoints));
        $bots = array_filter($bots, fn (TelegramBot $bot) => in_array($bot->getId(), $maxPointsIds, true));

        return array_values($bots);
    }

    public function calculateTelegramBotPoints(TelegramBot $bot, User $user): int
    {
        $points = 0;

        if ($user->getCountryCode() === null) {
            return 0;
        }

        if ($user->getCountryCode() !== $bot->getCountryCode()) {
            return 0;
        }

        $points += 1;

        if ($user->getLocaleCode() === $bot->getLocaleCode()) {
            $points += 1;
        }

        return $points;
    }
}