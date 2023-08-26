<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\CommandOptions;
use App\Entity\Feedback\Feedback;
use App\Exception\CommandLimitExceeded;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackTransfer;
use App\Service\Command\CommandLimitsChecker;
use App\Service\Command\CommandStatisticsProviderInterface;
use App\Service\Logger\ActivityLogger;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackCreator
{
    public function __construct(
        private readonly CommandOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly CommandStatisticsProviderInterface $statisticsProvider,
        private readonly CommandLimitsChecker $limitsChecker,
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly ActivityLogger $activityLogger,
    )
    {
    }

    public function getOptions(): CommandOptions
    {
        return $this->options;
    }

    /**
     * @param FeedbackTransfer $feedbackTransfer
     * @return Feedback
     * @throws CommandLimitExceeded
     * @throws SameMessengerUserException
     * @throws ValidatorException
     */
    public function createFeedback(FeedbackTransfer $feedbackTransfer): Feedback
    {
        $this->validator->validate($feedbackTransfer);

        $this->checkSearchTermUser($feedbackTransfer);

        $messengerUser = $feedbackTransfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        if (!$hasActiveSubscription) {
            $this->limitsChecker->checkCommandLimits($messengerUser->getUser(), $this->statisticsProvider);
        }

        $feedback = $this->constructFeedback($feedbackTransfer);

        $this->entityManager->persist($feedback);

        if ($this->options->shouldLogActivities()) {
            $this->activityLogger->logActivity($feedback);
        }

        return $feedback;
    }

    public function constructFeedback(FeedbackTransfer $feedbackTransfer): Feedback
    {
        $messengerUser = $feedbackTransfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);
        $searchTermTransfer = $feedbackTransfer->getSearchTerm();

        return new Feedback(
            $messengerUser->getUser(),
            $messengerUser,
            $searchTermTransfer->getText(),
            $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText(),
            $searchTermTransfer->getType(),
            null,
            $searchTermTransfer->getMessenger(),
            $searchTermTransfer->getMessengerUsername(),
            $feedbackTransfer->getRating(),
            $feedbackTransfer->getDescription(),
            $hasActiveSubscription,
            $messengerUser->getUser()->getCountryCode(),
            $messengerUser->getUser()->getLocaleCode()
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
        $searchTermTransfer = $feedbackTransfer->getSearchTerm();

        if (
            $messengerUser?->getUsername() !== null
            && $messengerUser?->getMessenger() !== null
            && $searchTermTransfer?->getMessengerUsername() !== null
            && strcasecmp($messengerUser->getUsername(), $searchTermTransfer->getMessengerUsername()) === 0
            && $messengerUser->getMessenger() === $searchTermTransfer->getMessenger()
        ) {
            throw new SameMessengerUserException($messengerUser);
        }
    }
}
