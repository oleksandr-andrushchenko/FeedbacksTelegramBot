<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Enum\Telegram\TelegramGroup;
use Symfony\Component\DependencyInjection\ServiceLocator;

class TelegramChannelFactory
{
    public function __construct(
        private readonly ServiceLocator $channelServiceLocator,
    )
    {
    }

    public function createTelegramChannel(TelegramGroup $name): TelegramChannelInterface
    {
        return $this->channelServiceLocator->get($name->name);
    }
}