<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\CommandOptions;
use App\Entity\Feedback\Feedback;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\CommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Service\Command\CommandLimitsChecker;
use App\Service\Command\CommandStatisticProviderInterface;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Logger\ActivityLogger;
use App\Service\Messenger\MessengerUserUpserter;
use App\Service\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackCreator
{
    public function __construct(
        private readonly CommandOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly CommandStatisticProviderInterface $statisticProvider,
        private readonly CommandLimitsChecker $limitsChecker,
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly ActivityLogger $activityLogger,
        private readonly MessengerUserUpserter $messengerUserUpserter,
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
     * @throws CommandLimitExceededException
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
            $this->limitsChecker->checkCommandLimits($messengerUser->getUser(), $this->statisticProvider);
        }

        $feedback = $this->constructFeedback($feedbackTransfer);

        $this->entityManager->persist($feedback);

        // todo: dispatch event and send in the background
        $this->logActivity($feedback);

        return $feedback;
    }

    public function constructFeedback(FeedbackTransfer $feedbackTransfer): Feedback
    {
        $messengerUser = $feedbackTransfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);
        $searchTermTransfer = $feedbackTransfer->getSearchTerm();

        if ($searchTermTransfer->getMessengerUser() === null) {
            $searchTermMessengerUser = null;
        } else {
            $searchTermMessengerUser = $this->messengerUserUpserter->upsertMessengerUser(
                $searchTermTransfer->getMessengerUser()
            );
        }

        return new Feedback(
            $messengerUser->getUser(),
            $messengerUser,
            $searchTermTransfer->getText(),
            $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText(),
            $searchTermTransfer->getType(),
            $searchTermMessengerUser,
            $searchTermMessengerUser?->getMessenger() ?? $searchTermTransfer->getMessenger(),
            $searchTermMessengerUser?->getUsername() ?? $searchTermTransfer->getMessengerUsername(),
            $feedbackTransfer->getRating(),
            $feedbackTransfer->getDescription(),
            $hasActiveSubscription,
            $messengerUser->getUser()->getCountryCode(),
            $messengerUser->getUser()->getLocaleCode(),
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

    private function logActivity(Feedback $feedback): void
    {
        if (!$this->options->shouldLogActivities()) {
            return;
        }

        $this->activityLogger->logActivity($feedback);
    }
}
