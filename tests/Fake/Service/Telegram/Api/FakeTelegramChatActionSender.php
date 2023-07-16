<?php

declare(strict_types=1);

namespace App\Tests\Fake\Service\Telegram\Api;

use App\Service\Telegram\Api\TelegramChatActionSenderInterface;
use App\Service\Telegram\Telegram;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class FakeTelegramChatActionSender implements TelegramChatActionSenderInterface
{
    public function __construct(private array $calls = [])
    {
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function sendChatAction(Telegram $telegram, int $chatId, string $action): ServerResponse
    {
        $this->calls[] = func_get_args();

        return Request::emptyResponse();
    }
}