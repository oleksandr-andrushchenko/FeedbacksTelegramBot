<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Feedback\FeedbackSearchUserTelegramNotification;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Message\Event\Feedback\FeedbackSearchUserTelegramNotificationCreatedEvent;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Feedback\FeedbackSearchSearcher;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\IdGenerator;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackSearchUsersTelegramNotifier implements FeedbackSearchUsersNotifierInterface
{
    public function __construct(
        private readonly FeedbackSearchSearcher $feedbackSearchSearcher,
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

    public function notifyFeedbackSearchUser(FeedbackSearchTerm $searchTerm, Feedback $feedback): void
    {
        $feedbackSearches = $this->feedbackSearchSearcher->searchFeedbackSearches($searchTerm);

        foreach ($feedbackSearches as $feedbackSearch) {
            $messengerUser = $feedbackSearch->getMessengerUser();

            if (
                $messengerUser !== null
                && $messengerUser->getMessenger() === Messenger::telegram
                && $messengerUser->getId() !== $feedback->getMessengerUser()->getId()
            ) {
                $this->notify($messengerUser, $searchTerm, $feedback, $feedbackSearch);
            }
        }
    }

    private function notify(
        MessengerUser $messengerUser,
        FeedbackSearchTerm $searchTerm,
        Feedback $feedback,
        FeedbackSearch $feedbackSearch
    ): void
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

            $notification = new FeedbackSearchUserTelegramNotification(
                $this->idGenerator->generateId(),
                $messengerUser,
                $searchTerm,
                $feedback,
                $feedbackSearch,
                $bot
            );
            $this->entityManager->persist($notification);

            $this->eventBus->dispatch(new FeedbackSearchUserTelegramNotificationCreatedEvent(notification: $notification));
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