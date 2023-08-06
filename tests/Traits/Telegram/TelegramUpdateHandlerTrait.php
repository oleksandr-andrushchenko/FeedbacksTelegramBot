<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Service\Telegram\Telegram;
use App\Tests\Traits\EntityManagerProviderTrait;
use Longman\TelegramBot\Entities\Update;
use Symfony\Component\HttpFoundation\Request;

trait TelegramUpdateHandlerTrait
{
    use TelegramUpdateHandlerProviderTrait;
    use EntityManagerProviderTrait;
    use TelegramRegistryProviderTrait;

    private function handleTelegramUpdate(?Telegram $telegram, Update $update): void
    {
        $this->getTelegramUpdateHandler()->handleTelegramUpdate(
            $telegram === null ? $this->getTelegramRegistry()->getTelegram('any_bot') : $telegram,
            new Request(content: $update->toJson())
        );
        $this->getEntityManager()->flush();
    }
}