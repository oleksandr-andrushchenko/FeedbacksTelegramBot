<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\CommandOptions;
use App\Entity\Feedback\Feedback;
use App\Exception\CommandLimitExceededException;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Message\Event\Feedback\FeedbackCreatedEvent;
use App\Service\Feedback\Command\CommandLimitsChecker;
use App\Service\Feedback\Command\CommandStatisticProviderInterface;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermUpserter;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\IdGenerator;
use App\Service\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackCreator
{
    public function __construct(
        private readonly CommandOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly CommandStatisticProviderInterface $statisticProvider,
        private readonly CommandLimitsChecker $limitsChecker,
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly FeedbackSearchTermUpserter $termUpserter,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
    )
    {
    }

    public function getOptions(): CommandOptions
    {
        return $this->options;
    }

    /**
     * @param FeedbackTransfer $transfer
     * @return Feedback
     * @throws CommandLimitExceededException
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
            if (
                $messengerUser?->getUsername() !== null
                && $messengerUser?->getMessenger() !== null
                && $searchTerm?->getMessengerUsername() !== null
                && strcasecmp($messengerUser->getUsername(), $searchTerm->getMessengerUsername()) === 0
                && $messengerUser->getMessenger() === $searchTerm->getMessenger()
            ) {
                throw new SameMessengerUserException($messengerUser);
            }
        }
    }
}
