<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\CommandOptions;
use App\Entity\Feedback\Feedback;
use App\Exception\CommandLimitExceededException;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Service\Feedback\Command\CommandLimitsChecker;
use App\Service\Feedback\Command\CommandStatisticProviderInterface;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermUpserter;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Logger\ActivityLogger;
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
        private readonly FeedbackSearchTermUpserter $termUpserter,
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

        $searchTerms = [];

        foreach ($feedbackTransfer->getSearchTerms() as $termTransfer) {
            $searchTerms[] = $this->termUpserter->upsertFeedbackSearchTerm($termTransfer);
        }

        return new Feedback(
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

    private function logActivity(Feedback $feedback): void
    {
        if (!$this->options->shouldLogActivities()) {
            return;
        }

        $this->activityLogger->logActivity($feedback);
    }
}
