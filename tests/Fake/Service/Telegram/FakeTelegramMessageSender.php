<?php

declare(strict_types=1);

namespace App\Tests\Fake\Service\Telegram;

use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramMessageSenderInterface;
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
        string $parseMode = null,
        int $replyToMessageId = null,
        bool $protectContent = null,
        bool $disableWebPagePreview = null
    ): ServerResponse
    {
        $this->calls[] = func_get_args();

        return Request::emptyResponse();
    }
}
