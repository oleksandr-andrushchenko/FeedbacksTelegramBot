<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\FeedbackLookupTargetAboutNewFeedbackLookupTelegramNotification;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Message\Command\Feedback\NotifyFeedbackLookupTargetAboutNewFeedbackLookupCommand;
use App\Repository\Feedback\FeedbackLookupRepository;
use App\Repository\Messenger\MessengerUserRepository;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\Telegram\Bot\View\FeedbackLookupTelegramViewProvider;
use App\Service\IdGenerator;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotifyFeedbackLookupTargetAboutNewFeedbackLookupCommandHandler
{
    public function __construct(
        private readonly FeedbackLookupRepository $feedbackLookupRepository,
        private readonly LoggerInterface $logger,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackLookupTelegramViewProvider $feedbackLookupTelegramViewProvider,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly IdGenerator $idGenerator,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function __invoke(NotifyFeedbackLookupTargetAboutNewFeedbackLookupCommand $command): void
    {
        $feedbackLookup = $command->getFeedbackLookup() ?? $this->feedbackLookupRepository->find($command->getFeedbackLookupId());

        if ($feedbackLookup === null) {
            $this->logger->warning(sprintf('No feedback lookup was found in %s for %s id', __CLASS__, $command->getFeedbackLookupId()));
            return;
        }

        $searchTerm = $feedbackLookup->getSearchTerm();
        $messengerUser = $searchTerm->getMessengerUser();

        if (
            $messengerUser !== null
            && $messengerUser->getMessenger() === Messenger::telegram
            && $messengerUser->getId() !== $feedbackLookup->getMessengerUser()->getId()
        ) {
            $this->notify($messengerUser, $searchTerm, $feedbackLookup);
            return;
        }

        // todo: process usernames from MessengerUser::$usernameHistory
        // todo: add search across unknown types (check if telegram type in types -> normalize text -> search)

        $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

        if ($messenger === Messenger::telegram) {
            $username = $searchTerm->getNormalizedText();

            $messengerUser = $this->messengerUserRepository->findOneByMessengerAndUsername($messenger, $username);

            if (
                $messengerUser !== null
                && $messengerUser->getId() !== $feedbackLookup->getMessengerUser()->getId()
            ) {
                $this->notify($messengerUser, $searchTerm, $feedbackLookup);
            }
        }
    }

    private function notify(MessengerUser $messengerUser, FeedbackSearchTerm $searchTerm, FeedbackLookup $feedbackLookup): void
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
                $this->getNotifyMessage($messengerUser, $bot, $feedbackLookup),
                keepKeyboard: true
            );

            $notification = new FeedbackLookupTargetAboutNewFeedbackLookupTelegramNotification(
                $this->idGenerator->generateId(),
                $messengerUser,
                $searchTerm,
                $feedbackLookup,
                $bot
            );
            $this->entityManager->persist($notification);
        }
    }

    private function getNotifyMessage(MessengerUser $messengerUser, TelegramBot $bot, FeedbackLookup $feedbackLookup): string
    {
        $localeCode = $messengerUser->getUser()->getLocaleCode();
        $message = '👋 ' . $this->translator->trans('might_be_interesting', domain: 'feedbacks.tg.notify', locale: $localeCode);
        $message = '<b>' . $message . '</b>';
        $message .= ':';
        $message .= "\n\n";
        $message .= $this->feedbackLookupTelegramViewProvider->getFeedbackLookupTelegramView(
            $bot,
            $feedbackLookup,
            addSecrets: true,
            addQuotes: true,
            localeCode: $localeCode
        );

        return $message;
    }
}