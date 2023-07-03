<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Enum\Telegram\TelegramName;
use App\Service\Telegram\Telegram;
use PHPUnit\Framework\MockObject\MockObject;
use Closure;

trait TelegramMockProviderTrait
{
    use TelegramRegistryProviderTrait;

    public function getTelegramMock(TelegramName $name, Closure $updateCallback, Closure $messengerUserCallback): Telegram|MockObject
    {
        $telegram = $this->getTelegramRegistry()->getTelegram($name);

        $telegramMock = $this->createMock(Telegram::class);

        $telegramMock->expects($this->any())->method('getName')->willReturn($telegram->getName());
        $telegramMock->expects($this->any())->method('getOptions')->willReturn($telegram->getOptions());
        $telegramMock->expects($this->any())->method('getUpdate')->willReturnCallback($updateCallback);
        $telegramMock->expects($this->any())->method('getMessengerUser')->willReturnCallback($messengerUserCallback);

        return $telegramMock;
    }
}