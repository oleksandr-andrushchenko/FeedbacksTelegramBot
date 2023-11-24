<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotRegistry;
use LogicException;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

class TelegramBotMessageSender implements TelegramBotMessageSenderInterface
{
    public function __construct(
        private readonly TelegramBotRegistry $telegramBotRegistry,
    )
    {
    }

    public function sendTelegramMessage(
        TelegramBot $botEntity,
        string|int $chatId,
        string $text,
        Keyboard $keyboard = null,
        string $parseMode = 'HTML',
        int $replyToMessageId = null,
        bool $protectContent = null,
        bool $disableWebPagePreview = true,
        bool $keepKeyboard = false
    ): ServerResponse
    {
        $bot = $this->telegramBotRegistry->getTelegramBot($botEntity);

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyToMessageId !== null) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }

        if ($parseMode !== null) {
            $data['parse_mode'] = $parseMode;
        }

        if ($keyboard === null) {
            if (!$keepKeyboard) {
                $data['reply_markup'] = Keyboard::remove();
            }
        } else {
            $data['reply_markup'] = $keyboard;
        }

        if ($protectContent !== null) {
            $data['protect_content'] = $protectContent;
        }

        if ($disableWebPagePreview !== null) {
            $data['disable_web_page_preview'] = $disableWebPagePreview;
        }

        if ($parseMode === 'HTML') {
            return $this->sendHtmlMessage($bot, $data);
        }

        return $bot->sendMessage($data);
    }

    private function sendHtmlMessage($bot, array $data, int $max = 4096): ServerResponse
    {
        $text = $data['text'];
        $length = mb_strlen($text);


        if ($length <= $max) {
            return $bot->sendMessage($data);
        }

        if ($text === strip_tags($text)) {
            return $bot->sendMessage($data);
        }

        do {
            $length = $max;

            while (true) {
                if ($length === 0) {
                    throw new LogicException('Not enough chunk size');
                }

                $expose = mb_substr($text, 0, $length);

                $countOpen = preg_match_all('#<[^/]#', $expose);
                $countClose = preg_match_all('#</#', $expose);

                if ($countOpen == $countClose) {
                    $data['text'] = $expose;
                    $response = $bot->sendMessage($data);
                    $text = mb_substr($text, mb_strlen($expose));
                    break;
                }

                $length--;
            }
        } while (!empty($text));

        return $response;
    }
}