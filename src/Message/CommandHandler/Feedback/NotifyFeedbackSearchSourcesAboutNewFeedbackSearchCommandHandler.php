<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Entity\Feedback\FeedbackNotification;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\FeedbackNotificationType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Message\Command\Feedback\NotifyFeedbackSearchSourcesAboutNewFeedbackSearchCommand;
use App\Message\Event\ActivityEvent;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Service\Feedback\FeedbackSearchSearcher;
use App\Service\Feedback\Telegram\Bot\View\FeedbackSearchTelegramViewProvider;
use App\Service\IdGenerator;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Bot\TelegramBotProvider;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotifyFeedbackSearchSourcesAboutNewFeedbackSearchCommandHandler
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackSearchSearcher $feedbackSearchSearcher,
        private readonly TelegramBotProvider $telegramBotProvider,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackSearchTelegramViewProvider $feedbackSearchTelegramViewProvider,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly IdGenerator $idGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $eventBus,
    )
    {
    }

    public function __invoke(NotifyFeedbackSearchSourcesAboutNewFeedbackSearchCommand $command): void
    {
        $feedbackSearch = $command->getFeedbackSearch() ?? $this->feedbackSearchRepository->find($command->getFeedbackSearchId());

        if ($feedbackSearch === null) {
            $this->logger->warning(sprintf('No feedback search was found in %s for %s id', __CLASS__, $command->getFeedbackSearchId()));
            return;
        }

        $feedbackSearches = $this->feedbackSearchSearcher->searchFeedbackSearches($feedbackSearch->getSearchTerm());

        foreach ($feedbackSearches as $targetFeedbackSearch) {
            // todo: iterate throw all $targetFeedbackSearch->getMessengerUser()->getUser()->getMessengerUsers()
            $messengerUser = $targetFeedbackSearch->getMessengerUser();

            if (
                $messengerUser !== null
                && $messengerUser->getMessenger() === Messenger::telegram
                && $messengerUser->getId() !== $feedbackSearch->getMessengerUser()->getId()
            ) {
                $this->notify($messengerUser, $feedbackSearch->getSearchTerm(), $feedbackSearch, $targetFeedbackSearch);
            }
        }
    }

    private function notify(
        MessengerUser $messengerUser,
        FeedbackSearchTerm $searchTerm,
        FeedbackSearch $feedbackSearch,
        FeedbackSearch $targetFeedbackSearch
    ): void
    {
        $botIds = $messengerUser->getBotIds();

        if ($botIds === null) {
            return;
        }

        $bots = $this->telegramBotProvider->getCachedTelegramBotsByGroupAndIds(TelegramBotGroupName::feedbacks, $botIds);

        foreach ($bots as $bot) {
            $this->telegramBotMessageSender->sendTelegramMessage(
                $bot,
                $messengerUser->getIdentifier(),
                $this->getNotifyMessage($messengerUser, $bot, $feedbackSearch),
                keepKeyboard: true
            );

            $notification = new FeedbackNotification(
                $this->idGenerator->generateId(),
                FeedbackNotificationType::feedback_search_source_about_new_feedback_search,
                $messengerUser,
                $searchTerm,
                feedbackSearch: $feedbackSearch,
                targetFeedbackSearch: $targetFeedbackSearch,
                telegramBot: $bot
            );
            $this->entityManager->persist($notification);

            $this->eventBus->dispatch(new ActivityEvent(entity: $notification, action: 'created'));
        }
    }

    private function getNotifyMessage(MessengerUser $messengerUser, TelegramBot $bot, FeedbackSearch $feedbackSearch): string
    {
        $localeCode = $messengerUser->getUser()->getLocaleCode();
        $message = '👋 ' . $this->translator->trans('might_be_interesting', domain: 'feedbacks.tg.notify', locale: $localeCode);
        $message = '<b>' . $message . '</b>';
        $message .= ':';
        $message .= "\n\n";
        $message .= $this->feedbackSearchTelegramViewProvider->getFeedbackSearchTelegramView(
            $bot,
            $feedbackSearch,
            addSecrets: $messengerUser->getUser()?->getSubscriptionExpireAt() < new DateTimeImmutable(),
            addQuotes: true,
            localeCode: $localeCode
        );

        return $message;
    }
}