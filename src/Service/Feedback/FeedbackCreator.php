<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Command\FeedbackCommandOptions;
use App\Entity\Feedback\Feedback;
use App\Enum\Messenger\Messenger;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Message\Event\Feedback\FeedbackCreatedEvent;
use App\Service\Feedback\Command\FeedbackCommandLimitsChecker;
use App\Service\Feedback\Command\FeedbackCommandStatisticProviderInterface;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermUpserter;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\IdGenerator;
use App\Service\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackCreator
{
    public function __construct(
        private readonly FeedbackCommandOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly FeedbackCommandStatisticProviderInterface $statisticProvider,
        private readonly FeedbackCommandLimitsChecker $limitsChecker,
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly FeedbackSearchTermUpserter $termUpserter,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
    )
    {
    }

    public function getOptions(): FeedbackCommandOptions
    {
        return $this->options;
    }

    /**
     * @param FeedbackTransfer $transfer
     * @return Feedback
     * @throws FeedbackCommandLimitExceededException
     * @throws SameMessengerUserException
     * @throws ValidatorException
     */
    public function createFeedback(FeedbackTransfer $transfer): Feedback
    {
        $this->validator->validate($transfer);

        $this->checkSearchTermUser($transfer);

        $messengerUser = $transfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        if (!$hasActiveSubscription) {
            $this->limitsChecker->checkCommandLimits($messengerUser->getUser(), $this->statisticProvider);
        }

        $feedback = $this->constructFeedback($transfer);
        $this->entityManager->persist($feedback);

        $this->eventBus->dispatch(new FeedbackCreatedEvent(feedback: $feedback));

        return $feedback;
    }

    public function constructFeedback(FeedbackTransfer $transfer): Feedback
    {
        $messengerUser = $transfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        $searchTerms = [];

        foreach ($transfer->getSearchTerms() as $termTransfer) {
            $searchTerms[] = $this->termUpserter->upsertFeedbackSearchTerm($termTransfer);
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
     * @throws SameMessengerUserException
     */
    private function checkSearchTermUser(FeedbackTransfer $transfer): void
    {
        $messengerUser = $transfer->getMessengerUser();

        foreach ($transfer->getSearchTerms() as $searchTerm) {
            $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

            if (
                $messengerUser?->getUsername() !== null
                && $messengerUser?->getMessenger() !== null
                && $messenger !== Messenger::unknown
                && strcasecmp($messengerUser->getUsername(), $searchTerm->getNormalizedText() ?? $searchTerm->getText()) === 0
                && $messengerUser->getMessenger() === $messenger
            ) {
                throw new SameMessengerUserException($messengerUser);
            }
        }
    }
}
