<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Activity;

use App\Entity\Feedback\Feedback;
use App\Repository\Telegram\TelegramChannelRepository;
use App\Service\Feedback\Telegram\View\FeedbackTelegramViewProvider;
use App\Service\Telegram\Api\TelegramMessageSender;
use App\Service\Telegram\Telegram;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class TelegramChannelFeedbackActivityPublisher
{
    public function __construct(
        private readonly TelegramChannelRepository $repository,
        private readonly TelegramMessageSender $messageSender,
        private readonly FeedbackTelegramViewProvider $viewProvider,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function publishTelegramChannelFeedbackActivity(Telegram $telegram, Feedback $feedback): void
    {
        $bot = $telegram->getBot();
        $channels = $this->repository->findPrimaryByGroupAndCountry($bot->getGroup(), $bot->getCountryCode());

        foreach ($channels as $channel) {
            try {
                $message = $this->viewProvider->getFeedbackTelegramView(
                    $telegram,
                    $feedback,
                    localeCode: $bot->getLocaleCode(),
                    showTime: false,
                    channel: $channel,
                );
                $chatId = '@' . $channel->getUsername();

                $response = $this->messageSender->sendTelegramMessage($telegram, $chatId, $message, keepKeyboard: true);

                if (!$response->isOk()) {
                    throw new RuntimeException($response->getDescription());
                }

                $messageId = $response->getResult()?->getMessageId();

                if ($messageId !== null) {
                    $feedback->addChannelMessageId($messageId);
                }
            } catch (Throwable $exception) {
                $this->logger->error($exception);
            }
        }
    }
}