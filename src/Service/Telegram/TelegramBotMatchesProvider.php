<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Telegram\TelegramGroup;
use App\Repository\Telegram\TelegramBotRepository;

class TelegramBotMatchesProvider
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
    )
    {
    }

    /**
     * @param User $user
     * @param TelegramGroup $group
     * @return TelegramBot[]
     */
    public function getTelegramBotMatches(User $user, TelegramGroup $group): array
    {
        $bots = $this->repository->findPrimaryByGroup($group);

        if (count($bots) === 0) {
            return [];
        }

        $bots = array_combine(array_map(fn (TelegramBot $bot) => $bot->getId(), $bots), $bots);

        $points = [];

        foreach ($bots as $id => $bot) {
            $points[$id] = $this->calculateTelegramBotPoints($user, $bot);
        }

        $points = array_filter($points);

        if (count($points) === 0) {
            return [];
        }

        asort($points);

        $lastMaxPointsId = array_key_last($points);
        $maxPoints = $points[$lastMaxPointsId];

        // todo: add previous bots layer if bot has locality

        $maxPointsIds = array_keys(array_filter($points, fn (int $pts) => $pts === $maxPoints));

        $bots = array_filter($bots, fn (TelegramBot $bot) => in_array($bot->getId(), $maxPointsIds, true));

        return array_values($bots);
    }

    public function calculateTelegramBotPoints(User $user, TelegramBot $bot): int
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