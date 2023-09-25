<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Activity;

use App\Entity\Feedback\Feedback;
use App\Service\Feedback\Telegram\View\FeedbackTelegramViewProvider;
use App\Service\Telegram\Api\TelegramMessageSender;
use App\Service\Telegram\Telegram;
use Longman\TelegramBot\Entities\Message;
use RuntimeException;

class FeedbackActivityTelegramChatSender
{
    public function __construct(
        private readonly TelegramMessageSender $messageSender,
        private readonly FeedbackTelegramViewProvider $viewProvider,
    )
    {
    }

    public function sendFeedbackActivityToTelegramChat(
        Telegram $telegram,
        Feedback $feedback,
        string $channelUsername = null
    ): ?Message
    {
        $bot = $telegram->getBot();

        $channelUsername = $channelUsername ?? $bot->getChannelUsername();

        if ($channelUsername === null) {
            throw new RuntimeException(sprintf('"%s" telegram bot does not have channel to post to', $bot->getUsername()));
        }

        $message = $this->viewProvider->getFeedbackTelegramView(
            $telegram,
            $feedback,
            localeCode: $bot->getLocaleCode(),
            showTime: false
        );

        $response = $this->messageSender->sendTelegramMessage($telegram, '@' . $channelUsername, $message, keepKeyboard: true);

        if (!$response->isOk()) {
            throw new RuntimeException($response->getDescription());
        }

        return $response->getResult();
    }
}