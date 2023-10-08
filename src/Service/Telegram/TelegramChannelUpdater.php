<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramChannel;
use App\Object\Telegram\TelegramChannelTransfer;

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
        if ($channelTransfer->region1Passed()) {
            $channel->setRegion1($channelTransfer->getRegion1());
        }
        if ($channelTransfer->region2Passed()) {
            $channel->setRegion2($channelTransfer->getRegion2());
        }
        if ($channelTransfer->localityPassed()) {
            $channel->setLocality($channelTransfer->getLocality());
        }
        if ($channelTransfer->primaryPassed()) {
            $channel->setPrimary($channelTransfer->primary());
        }

        $this->validator->validateTelegramChannel($channel);
    }
}