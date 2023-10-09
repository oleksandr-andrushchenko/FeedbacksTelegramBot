<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramChannel;
use App\Transfer\Telegram\TelegramChannelTransfer;
use DateTimeImmutable;

class TelegramChannelUpdater
{
    public function __construct(
        private readonly TelegramChannelValidator $validator,
    )
    {
    }

    /**
     * @param TelegramChannel $channel
     * @param TelegramChannelTransfer $channelTransfer
     * @return void
     */
    public function updateTelegramChannel(TelegramChannel $channel, TelegramChannelTransfer $channelTransfer): void
    {
        if ($channelTransfer->groupPassed()) {
            $channel->setGroup($channelTransfer->getGroup());
        }

        if ($channelTransfer->namePassed()) {
            $channel->setName($channelTransfer->getName());
        }

        if ($channelTransfer->countryPassed()) {
            $channel->setCountryCode($channelTransfer->getCountry()->getCode());
        }
        if ($channelTransfer->localePassed()) {
            $channel->setLocaleCode($channelTransfer->getLocale()->getCode());
        }
        if ($channelTransfer->administrativeAreaLevel1Passed()) {
            $channel->setAdministrativeAreaLevel1($channelTransfer->getAdministrativeAreaLevel1());
        }
        if ($channelTransfer->administrativeAreaLevel2Passed()) {
            $channel->setAdministrativeAreaLevel2($channelTransfer->getAdministrativeAreaLevel2());
        }
        if ($channelTransfer->administrativeAreaLevel3Passed()) {
            $channel->setAdministrativeAreaLevel3($channelTransfer->getAdministrativeAreaLevel3());
        }
        if ($channelTransfer->primaryPassed()) {
            $channel->setPrimary($channelTransfer->primary());
        }

        $channel->setUpdatedAt(new DateTimeImmutable());

        $this->validator->validateTelegramChannel($channel);
    }
}