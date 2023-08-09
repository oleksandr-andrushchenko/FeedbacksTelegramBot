<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Repository\Telegram\TelegramBotRepository;
use PHPUnit\Framework\MockObject\MockObject;

trait TelegramBotRepositoryMockProviderTrait
{
    public function getTelegramBotRepositoryMock(bool $replace = true): TelegramBotRepository|MockObject
    {
        $mock = $this->createMock(TelegramBotRepository::class);

        if ($replace) {
            static::getContainer()->set('app.repository.telegram_bot', $mock);
        }

        return $mock;
    }
}