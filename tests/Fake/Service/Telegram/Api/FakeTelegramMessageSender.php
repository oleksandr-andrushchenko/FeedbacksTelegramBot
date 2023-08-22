<?php

declare(strict_types=1);

namespace App\Tests\Fake\Service\Telegram\Api;

use App\Service\Telegram\Api\TelegramMessageSenderInterface;
use App\Service\Telegram\Telegram;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class FakeTelegramMessageSender implements TelegramMessageSenderInterface
{
    public function __construct(private array $calls = [])
    {
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function sendTelegramMessage(
        Telegram $telegram,
        int $chatId,
        string $text,
        Keyboard $keyboard = null,
        string $parseMode = 'HTML',
        int $replyToMessageId = null,
        bool $protectContent = null,
        bool $disableWebPagePreview = true
    ): ServerResponse
    {
        $this->calls[] = func_get_args();

        return Request::emptyResponse();
    }
}
