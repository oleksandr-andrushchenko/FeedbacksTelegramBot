<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Entity\Telegram\TelegramChannel;
use App\Message\Event\Feedback\FeedbackSendToTelegramChannelConfirmReceivedEvent;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Channel\TelegramChannelMatchesProvider;
use App\Service\Telegram\Channel\View\TelegramChannelLinkViewProvider;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackSendToTelegramChannelConfirmReceivedEventHandler
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly TelegramChannelMatchesProvider $telegramChannelMatchesProvider,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
        private readonly TelegramChannelLinkViewProvider $telegramChannelLinkViewProvider,
        private readonly TranslatorInterface $translator,
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

        $addTime = $event->addTime();
        $notifyUser = $event->notifyUser();
        $channels = $this->telegramChannelMatchesProvider->getCachedTelegramChannelMatches($feedback->getUser(), $bot);

        foreach ($channels as $channel) {
            $message = $this->feedbackTelegramViewProvider->getFeedbackTelegramView(
                $bot,
                $feedback,
                addSecrets: true,
                addSign: true,
                addTime: $addTime,
                channel: $channel,
                localeCode: $channel->getLocaleCode(),
            );

            $chatId = $channel->getChatId() ?? ('@' . $channel->getUsername());

            $response = $this->telegramBotMessageSender->sendTelegramMessage($bot, $chatId, $message, keepKeyboard: true);

            if (!$response->isOk()) {
                $this->logger->error($response->getDescription());
                continue;
            }

            $messageId = $response->getResult()?->getMessageId();

            if ($messageId !== null) {
                $feedback->addChannelMessageId($messageId);
            }
        }

        if ($notifyUser) {
            $messengerUser = $feedback->getMessengerUser();
            $userLocaleCode = $messengerUser->getUser()->getLocaleCode();
            $userChatId = $messengerUser->getIdentifier();
            $searchTermView = $this->feedbackTelegramViewProvider->getFeedbackSearchTermsTelegramView($feedback->getSearchTerms()->toArray(), localeCode: $userLocaleCode);
            $channelViews = implode(
                ', ',
                array_map(
                    fn (TelegramChannel $channel): string => $this->telegramChannelLinkViewProvider->getTelegramChannelLinkView($channel, html: true),
                    $channels
                )
            );
            $parameters = [
                'search_term' => $searchTermView,
                'channels' => $channelViews,
            ];
            $message = '🫡 ';
            $message .= $this->translator->trans('feedback_published', parameters: $parameters, domain: 'feedbacks.tg.notify', locale: $userLocaleCode);

            $this->telegramBotMessageSender->sendTelegramMessage($bot, $userChatId, $message, keepKeyboard: true);
        }
    }
}