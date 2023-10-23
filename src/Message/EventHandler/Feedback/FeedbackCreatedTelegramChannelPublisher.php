<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Event\Feedback\FeedbackSendToTelegramChannelConfirmReceivedEvent;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Bot\TelegramBotRegistry;
use App\Service\Telegram\Channel\TelegramChannelMatchesProvider;
use Psr\Log\LoggerInterface;

class FeedbackCreatedTelegramChannelPublisher
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly TelegramBotRegistry $telegramBotRegistry,
        private readonly TelegramChannelMatchesProvider $channelMatchesProvider,
        private readonly TelegramBotMessageSenderInterface $messageSender,
        private readonly FeedbackTelegramViewProvider $viewProvider,
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

        $telegramBot = $event->getTelegramBot() ?? $feedback->getTelegramBot();

        if ($telegramBot === null) {
            $this->logger->notice(sprintf('No telegram bot was found in %s for %s id', __CLASS__, $event->getFeedbackId()));
            return;
        }

        $bot = $this->telegramBotRegistry->getTelegramBot($telegramBot);

        $channels = $this->channelMatchesProvider->getTelegramChannelMatches(
            $bot->getMessengerUser()->getUser(),
            $bot->getEntity()
        );

        foreach ($channels as $channel) {
            $message = $this->viewProvider->getFeedbackTelegramView(
                $bot,
                $feedback,
                localeCode: $channel->getLocaleCode(),
                showTime: false,
                channel: $channel,
            );
            $chatId = $channel->getChatId() ?? ('@' . $channel->getUsername());

            $response = $this->messageSender->sendTelegramMessage(
                $bot->getEntity(),
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
        }
    }
}