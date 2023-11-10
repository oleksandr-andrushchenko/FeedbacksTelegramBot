<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Entity\Feedback\Feedback;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\Feedback\FeedbackOnOneselfException;
use App\Exception\ValidatorException;
use App\Message\Event\ActivityEvent;
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
     * @param FeedbackTransfer $transfer
     * @return Feedback
     * @throws FeedbackCommandLimitExceededException
     * @throws FeedbackOnOneselfException
     * @throws ValidatorException
     */
    public function createFeedback(FeedbackTransfer $transfer): Feedback
    {
        $this->validator->validate($transfer);

        $this->checkSearchTermUser($transfer);

        $messengerUser = $transfer->getMessengerUser();
        $hasActiveSubscription = $this->feedbackSubscriptionManager->hasActiveSubscription($messengerUser);

        if (!$hasActiveSubscription) {
            $this->feedbackCommandLimitsChecker->checkCommandLimits(
                $messengerUser->getUser(),
                $this->feedbackCommandStatisticProvider
            );
        }

        $feedback = $this->constructFeedback($transfer);
        $this->entityManager->persist($feedback);

        $this->eventBus->dispatch(new ActivityEvent(entity: $feedback));
        $this->eventBus->dispatch(new FeedbackCreatedEvent(feedback: $feedback));

        return $feedback;
    }

    public function constructFeedback(FeedbackTransfer $transfer): Feedback
    {
        $messengerUser = $transfer->getMessengerUser();
        $hasActiveSubscription = $this->feedbackSubscriptionManager->hasActiveSubscription($messengerUser);

        $searchTerms = [];

        foreach ($transfer->getSearchTerms() as $searchTerm) {
            $searchTerms[] = $this->feedbackSearchTermUpserter->upsertFeedbackSearchTerm($searchTerm);
        }

        return new Feedback(
            $this->idGenerator->generateId(),
            $messengerUser->getUser(),
            $messengerUser,
            $searchTerms,
            $transfer->getRating(),
            description: $transfer->getDescription(),
            hasActiveSubscription: $hasActiveSubscription,
            countryCode: $messengerUser->getUser()->getCountryCode(),
            localeCode: $messengerUser->getUser()->getLocaleCode(),
            telegramBot: $transfer->getTelegramBot()
        );
    }

    /**
     * @param FeedbackTransfer $transfer
     * @return void
     * @throws FeedbackOnOneselfException
     */
    private function checkSearchTermUser(FeedbackTransfer $transfer): void
    {
        $messengerUser = $transfer->getMessengerUser();

        foreach ($transfer->getSearchTerms() as $searchTerm) {
            $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

            if (
                $messengerUser?->getUsername() !== null
                && $messengerUser?->getMessenger() !== null
                && strcasecmp($messengerUser->getUsername(), $searchTerm->getNormalizedText() ?? $searchTerm->getText()) === 0
                && $messengerUser->getMessenger() === $messenger
            ) {
                throw new FeedbackOnOneselfException($messengerUser);
            }
        }
    }
}
