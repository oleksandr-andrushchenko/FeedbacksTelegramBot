<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Group;

use App\Enum\Telegram\TelegramBotGroupName;
use Symfony\Component\DependencyInjection\ServiceLocator;

class TelegramBotGroupFactory
{
    public function __construct(
        private readonly ServiceLocator $channelServiceLocator,
    )
    {
    }

    public function createTelegramChannel(TelegramBotGroupName $name): TelegramBotGroupInterface
    {
        return $this->channelServiceLocator->get($name->name);
    }
}
