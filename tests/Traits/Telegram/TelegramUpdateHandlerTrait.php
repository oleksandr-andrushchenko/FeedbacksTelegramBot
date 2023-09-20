<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Service\Telegram\Telegram;
use App\Tests\Fixtures;
use App\Tests\Traits\EntityManagerProviderTrait;
use Longman\TelegramBot\Entities\Update;
use Symfony\Component\HttpFoundation\Request;

trait TelegramUpdateHandlerTrait
{
    use TelegramUpdateHandlerProviderTrait;
    use EntityManagerProviderTrait;
    use TelegramRegistryProviderTrait;
    use TelegramBotRepositoryProviderTrait;

    private function handleTelegramUpdate(?Telegram $telegram, Update $update): void
    {
        if ($telegram === null) {
            $bot = $this->getTelegramBotRepository()->findOneByUsername(Fixtures::BOT_USERNAME_1);
            $telegram = $this->getTelegramRegistry()->getTelegram($bot);
        }

        $this->getTelegramUpdateHandler()->handleTelegramUpdate($telegram, new Request(content: $update->toJson()));
        $this->getEntityManager()->flush();
    }
}