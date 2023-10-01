<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Telegram\TelegramGroup;
use App\Repository\Telegram\TelegramBotRepository;

class BetterMatchTelegramBotProvider
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
    public function getBetterMatchTelegramBots(User $user, TelegramGroup $group): array
    {
        $bots = $this->repository->findByGroup($group);

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

        if ($user->getAddressLocality() === null) {
            if ($bot->getRegion1() === null) {
                $points += 4;
            } else {
                if ($bot->getRegion2() === null) {
                    $points += 2;
                } else {
                    return 0;
                }
            }

            goto out;
        }

        if ($bot->getRegion1() === null) {
            $points += 8;
            goto out;
        }

        if ($user->getAddressLocality()->getRegion1() !== $bot->getRegion1()) {
            return 0;
        }

        $points += 16;

        if ($bot->getRegion2() === null) {
            $points += 32;
            goto out;
        }

        if ($user->getAddressLocality()->getRegion2() !== $bot->getRegion2()) {
            return 0;
        }

        $points += 64;

        if ($bot->getLocality() === null) {
            $points += 128;
            goto out;
        }

        if ($user->getAddressLocality()->getLocality() !== $bot->getLocality()) {
            return 0;
        }

        $points += 256;

        out:

        if ($user->getLocaleCode() === $bot->getLocaleCode()) {
            $points += 1;
        }

        return $points;
    }
}