<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Event\Feedback\FeedbackSendToTelegramChannelConfirmReceivedEvent;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Channel\TelegramChannelMatchesProvider;
use Psr\Log\LoggerInterface;
use Throwable;

class FeedbackSendToTelegramChannelConfirmReceivedEventHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly TelegramChannelMatchesProvider $telegramChannelMatchesProvider,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(FeedbackSendToTelegramChannelConfirmReceivedEvent $event): void
    {
        $feedback = $event->getFeedback() ?? $this->feedbackRepository->find($event->getFeedbackId());

        if ($feedback === null) {
            $this->logger->warning(sprintf('No feedback was found in %s for %s id', __CLASS__, $event->getFeedbackId()));
            return;
        }

        $bot = $feedback->getTelegramBot();

        if ($bot === null) {
            $this->logger->notice(sprintf('No telegram bot was found in %s for %s id', __CLASS__, $event->getFeedbackId()));
            return;
        }

        $showTime = $event->showTime() ?? false;

        $channels = $this->telegramChannelMatchesProvider->getCachedTelegramChannelMatches($feedback->getUser(), $bot);

        foreach ($channels as $channel) {
            try {
                $message = $this->feedbackTelegramViewProvider->getFeedbackTelegramView(
                    $bot,
                    $feedback,
                    secrets: true,
                    time: $showTime,
                    channel: $channel,
                    localeCode: $channel->getLocaleCode(),
                );

                $chatId = $channel->getChatId() ?? ('@' . $channel->getUsername());

                $response = $this->telegramBotMessageSender->sendTelegramMessage(
                    $bot,
                    $chatId,
                    $message,
                    keepKeyboard: true
                );

                if (!$response->isOk()) {
                    $this->logger->error($response->getDescription());
                    continue;
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