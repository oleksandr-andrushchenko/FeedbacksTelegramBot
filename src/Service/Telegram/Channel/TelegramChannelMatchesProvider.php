<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Entity\User\User;
use App\Repository\Telegram\Channel\TelegramChannelRepository;

class TelegramChannelMatchesProvider
{
    public function __construct(
        private readonly TelegramChannelRepository $repository,
    )
    {
    }

    /**
     * @param User $user
     * @param TelegramBot $bot
     * @return TelegramChannel[]
     */
    public function getTelegramChannelMatches(User $user, TelegramBot $bot): array
    {
        $channels = $this->repository->findPrimaryByGroup($bot->getGroup());

        if (count($channels) === 0) {
            return [];
        }

        $channels = array_combine(array_map(fn (TelegramChannel $channel) => $channel->getId(), $channels), $channels);

        $points = [];

        foreach ($channels as $id => $channel) {
            $points[$id] = $this->calculateTelegramChannelPoints($bot, $user, $channel);
        }

        $points = array_filter($points);

        if (count($points) === 0) {
            return [];
        }

        asort($points);

        $points = array_filter($points);
//        var_dump($points);die;

        $channels = array_filter($channels, fn (TelegramChannel $channel) => array_key_exists($channel->getId(), $points));

        return array_values($channels);
    }

    public function calculateTelegramChannelPoints(TelegramBot $bot, User $user, TelegramChannel $channel): int
    {
        $points = 0;

        if ($bot->getCountryCode() !== $channel->getCountryCode()) {
            return 0;
        }

        $points += 1;

        if ($user->getCountryCode() !== null && $user->getCountryCode() !== $bot->getCountryCode()) {
            if ($channel->getAdministrativeAreaLevel1() !== null) {
                return 0;
            }

            return $points;
        }

        if ($user->getAddress() === null && $channel->getAdministrativeAreaLevel1() !== null) {
            return 0;
        }

        if ($channel->getAdministrativeAreaLevel1() === null) {
            return $points;
        }

        if ($user->getAddress()->getAdministrativeAreaLevel1() !== $channel->getAdministrativeAreaLevel1()) {
            return 0;
        }

        $points += 2;

//        if ($channel->getAdministrativeAreaLevel2() === null && $channel->getAdministrativeAreaLevel3() === null) {
//            if ($user->getAddress()->getAdministrativeAreaLevel2() !== null || $user->getAddress()->getAdministrativeAreaLevel3() !== null) {
//                return $points;
//            }
//        }

        if ($channel->getAdministrativeAreaLevel2() !== null && $user->getAddress()->getAdministrativeAreaLevel2() !== $channel->getAdministrativeAreaLevel2()) {
            return 0;
        }

        $points += 4;

        if ($channel->getAdministrativeAreaLevel3() !== null && $user->getAddress()->getAdministrativeAreaLevel3() !== $channel->getAdministrativeAreaLevel3()) {
            return 0;
        }

        $points += 8;

        return $points;
    }
}