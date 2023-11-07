<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Feedback\FeedbackSearchTermUserTelegramNotification;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Message\Event\Feedback\FeedbackSearchTermUserTelegramNotificationCreatedEvent;
use App\Repository\Messenger\MessengerUserRepository;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\IdGenerator;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackSearchTermUsersTelegramNotifier implements FeedbackSearchTermUsersNotifierInterface
{
    public function __construct(
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly IdGenerator $idGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $eventBus,
    )
    {
    }

    public function notifyFeedbackSearchTermUser(FeedbackSearchTerm $searchTerm, Feedback $feedback): void
    {
        $messengerUser = $searchTerm->getMessengerUser();

        if (
            $messengerUser !== null
            && $messengerUser->getMessenger() === Messenger::telegram
            && $messengerUser->getId() !== $feedback->getMessengerUser()->getId()
        ) {
            $this->notify($messengerUser, $searchTerm, $feedback);
            return;
        }

        // todo: process usernames from MessengerUser::$usernameHistory
        // todo: add search across unknown types (check if telegram type in types -> normalize text -> search)

        $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

        if ($messenger === Messenger::telegram) {
            $username = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

            $messengerUser = $this->messengerUserRepository->findOneByMessengerAndUsername($messenger, $username);

            if (
                $messengerUser !== null
                && $messengerUser->getId() !== $feedback->getMessengerUser()->getId()
            ) {
                $this->notify($messengerUser, $searchTerm, $feedback);
            }
        }
    }

    private function notify(MessengerUser $messengerUser, FeedbackSearchTerm $searchTerm, Feedback $feedback): void
    {
        $botIds = $messengerUser->getBotIds();

        if ($botIds === null) {
            return;
        }

        $bots = $this->telegramBotRepository->findPrimaryByGroupAndIds(TelegramBotGroupName::feedbacks, $botIds);

        foreach ($bots as $bot) {
            $this->telegramBotMessageSender->sendTelegramMessage(
                $bot,
                $messengerUser->getIdentifier(),
                $this->getNotifyMessage($messengerUser, $bot, $feedback),
                keepKeyboard: true
            );

            $notification = new FeedbackSearchTermUserTelegramNotification(
                $this->idGenerator->generateId(),
                $messengerUser,
                $searchTerm,
                $feedback,
                $bot
            );
            $this->entityManager->persist($notification);

            $this->eventBus->dispatch(new FeedbackSearchTermUserTelegramNotificationCreatedEvent(notification: $notification));
        }
    }

    private function getNotifyMessage(MessengerUser $messengerUser, TelegramBot $bot, Feedback $feedback): string
    {
        $localeCode = $messengerUser->getUser()->getLocaleCode();
        $message = '👋 ' . $this->translator->trans('might_be_interesting', domain: 'feedbacks.tg.notify', locale: $localeCode);
        $message = '<b>' . $message . '</b>';
        $message .= ':';
        $message .= "\n\n";
        $message .= $this->feedbackTelegramViewProvider->getFeedbackTelegramView(
            $bot,
            $feedback,
            addSecrets: true,
            addQuotes: true,
            localeCode: $localeCode
        );

        return $message;
    }
}