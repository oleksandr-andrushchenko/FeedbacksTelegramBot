<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Telegram\TelegramChannel;
use App\Object\Telegram\TelegramChannelTransfer;
use Doctrine\ORM\EntityManagerInterface;

class TelegramChannelCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramChannelValidator $validator,
    )
    {
    }

    /**
     * @param TelegramChannelTransfer $channelTransfer
     * @return TelegramChannel
     */
    public function createTelegramChannel(TelegramChannelTransfer $channelTransfer): TelegramChannel
    {
        $channel = new TelegramChannel(
            $channelTransfer->getUsername(),
            $channelTransfer->getGroup(),
            $channelTransfer->getName(),
            $channelTransfer->getCountry()->getCode(),
            $channelTransfer->getLocale()?->getCode() ?? $channelTransfer->getCountry()->getLocaleCodes()[0],
            region1: $channelTransfer->getRegion1(),
            region2: $channelTransfer->getRegion2(),
            locality: $channelTransfer->getLocality(),
            primary: $channelTransfer->primary(),
        );

        $this->validator->validateTelegramChannel($channel);

        $this->entityManager->persist($channel);

        return $channel;
    }
}