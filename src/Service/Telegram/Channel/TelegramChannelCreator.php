<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramChannel;
use App\Transfer\Telegram\TelegramChannelTransfer;
use Doctrine\ORM\EntityManagerInterface;

class TelegramChannelCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramChannelValidator $validator,
    )
    {
    }

    public function createTelegramChannel(TelegramChannelTransfer $channelTransfer): TelegramChannel
    {
        $channel = new TelegramChannel(
            $channelTransfer->getUsername(),
            $channelTransfer->getGroup(),
            $channelTransfer->getName(),
            $channelTransfer->getCountry()->getCode(),
            $channelTransfer->getLocale()?->getCode() ?? $channelTransfer->getCountry()->getLocaleCodes()[0],
            administrativeAreaLevel1: $channelTransfer->getAdministrativeAreaLevel1(),
            administrativeAreaLevel2: $channelTransfer->getAdministrativeAreaLevel2(),
            administrativeAreaLevel3: $channelTransfer->getAdministrativeAreaLevel3(),
            primary: $channelTransfer->primary(),
        );

        $this->validator->validateTelegramChannel($channel);

        $this->entityManager->persist($channel);

        return $channel;
    }
}