<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Message\Command\Feedback\NotifyFeedbackSearchSourcesAboutNewFeedbackSearchCommand;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Feedback\FeedbackSearchSearcher;
use App\Service\Feedback\Telegram\Bot\View\FeedbackSearchTelegramViewProvider;
use App\Service\IdGenerator;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotifyFeedbackSearchSourcesAboutNewFeedbackSearchCommandHandler
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackSearchSearcher $feedbackSearchSearcher,
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackSearchTelegramViewProvider $feedbackSearchTelegramViewProvider,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly IdGenerator $idGenerator,
        private readonly EntityManagerInterface $entityManager,
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

        $bots = $this->telegramBotRepository->findPrimaryByGroupAndIds(TelegramBotGroupName::feedbacks, $botIds);

        foreach ($bots as $bot) {
            $this->telegramBotMessageSender->sendTelegramMessage(
                $bot,
                $messengerUser->getIdentifier(),
                $this->getNotifyMessage($messengerUser, $bot, $feedbackSearch),
                keepKeyboard: true
            );

            $notification = new FeedbackSearchSourceAboutNewFeedbackSearchTelegramNotification(
                $this->idGenerator->generateId(),
                $messengerUser,
                $searchTerm,
                $feedbackSearch,
                $targetFeedbackSearch,
                $bot
            );
            $this->entityManager->persist($notification);
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
            addSecrets: true,
            addQuotes: true,
            localeCode: $localeCode
        );

        return $message;
    }
}