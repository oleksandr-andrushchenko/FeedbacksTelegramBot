<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Entity\Feedback\Feedback;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Message\Event\Feedback\FeedbackCreatedEvent;
use App\Service\Feedback\Command\FeedbackCommandLimitsChecker;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermUpserter;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\Statistic\FeedbackUserStatisticProviderInterface;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\IdGenerator;
use App\Service\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackCreator
{
    public function __construct(
        private readonly FeedbackCommandOptions $feedbackCommandOptions,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly FeedbackUserStatisticProviderInterface $feedbackCommandStatisticProvider,
        private readonly FeedbackCommandLimitsChecker $feedbackCommandLimitsChecker,
        private readonly FeedbackSubscriptionManager $feedbackSubscriptionManager,
        private readonly FeedbackSearchTermUpserter $feedbackSearchTermUpserter,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
    )
    {
    }

    public function getOptions(): FeedbackCommandOptions
    {
        return $this->feedbackCommandOptions;
    }

    /**
     * @param FeedbackTransfer $feedbackTransfer
     * @return Feedback
     * @throws FeedbackCommandLimitExceededException
     * @throws SameMessengerUserException
     * @throws ValidatorException
     */
    public function createFeedback(FeedbackTransfer $feedbackTransfer): Feedback
    {
        $this->validator->validate($feedbackTransfer);

        $this->checkSearchTermUser($feedbackTransfer);

        $messengerUser = $feedbackTransfer->getMessengerUser();
        $hasActiveSubscription = $this->feedbackSubscriptionManager->hasActiveSubscription($messengerUser);

        if (!$hasActiveSubscription) {
            $this->feedbackCommandLimitsChecker->checkCommandLimits(
                $messengerUser->getUser(),
                $this->feedbackCommandStatisticProvider
            );
        }

        $feedback = $this->constructFeedback($feedbackTransfer);
        $this->entityManager->persist($feedback);

        $this->eventBus->dispatch(new FeedbackCreatedEvent(feedback: $feedback));

        return $feedback;
    }

    public function constructFeedback(FeedbackTransfer $feedbackTransfer): Feedback
    {
        $messengerUser = $feedbackTransfer->getMessengerUser();
        $hasActiveSubscription = $this->feedbackSubscriptionManager->hasActiveSubscription($messengerUser);

        $searchTerms = [];

        foreach ($feedbackTransfer->getSearchTerms() as $searchTerm) {
            $searchTerms[] = $this->feedbackSearchTermUpserter->upsertFeedbackSearchTerm($searchTerm);
        }

        return new Feedback(
            $this->idGenerator->generateId(),
            $messengerUser->getUser(),
            $messengerUser,
            $searchTerms,
            $feedbackTransfer->getRating(),
            description: $feedbackTransfer->getDescription(),
            hasActiveSubscription: $hasActiveSubscription,
            countryCode: $messengerUser->getUser()->getCountryCode(),
            localeCode: $messengerUser->getUser()->getLocaleCode(),
            telegramBot: $feedbackTransfer->getTelegramBot()
        );
    }

    /**
     * @param FeedbackTransfer $feedbackTransfer
     * @return void
     * @throws SameMessengerUserException
     */
    private function checkSearchTermUser(FeedbackTransfer $feedbackTransfer): void
    {
        $messengerUser = $feedbackTransfer->getMessengerUser();

        foreach ($feedbackTransfer->getSearchTerms() as $searchTerm) {
            $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

            if (
                $messengerUser?->getUsername() !== null
                && $messengerUser?->getMessenger() !== null
                && strcasecmp($messengerUser->getUsername(), $searchTerm->getNormalizedText() ?? $searchTerm->getText()) === 0
                && $messengerUser->getMessenger() === $messenger
            ) {
                throw new SameMessengerUserException($messengerUser);
            }
        }
    }
}
