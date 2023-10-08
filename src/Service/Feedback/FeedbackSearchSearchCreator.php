<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\CommandOptions;
use App\Entity\Feedback\FeedbackSearchSearch;
use App\Exception\CommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Service\Command\CommandLimitsChecker;
use App\Service\Command\CommandStatisticProviderInterface;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Logger\ActivityLogger;
use App\Service\Validator;
use App\Transfer\Feedback\FeedbackSearchSearchTransfer;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackSearchSearchCreator
{
    public function __construct(
        private readonly CommandOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly CommandStatisticProviderInterface $statisticProvider,
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
     * @param FeedbackSearchSearchTransfer $feedbackSearchLookupTransfer
     * @return FeedbackSearchSearch
     * @throws CommandLimitExceededException
     * @throws ValidatorException
     */
    public function createFeedbackSearchSearch(FeedbackSearchSearchTransfer $feedbackSearchLookupTransfer): FeedbackSearchSearch
    {
        $this->validator->validate($feedbackSearchLookupTransfer);

        $messengerUser = $feedbackSearchLookupTransfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        if (!$hasActiveSubscription) {
            $this->limitsChecker->checkCommandLimits($messengerUser->getUser(), $this->statisticProvider);
        }

        $searchTermTransfer = $feedbackSearchLookupTransfer->getSearchTerm();
        $searchTermMessengerUser = null;

        $feedbackSearchSearch = new FeedbackSearchSearch(
            $messengerUser->getUser(),
            $messengerUser,
            $searchTermTransfer->getText(),
            $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText(),
            $searchTermTransfer->getType(),
            $searchTermMessengerUser,
            $searchTermTransfer->getMessenger(),
            $searchTermTransfer->getMessengerUsername(),
            $hasActiveSubscription,
            $messengerUser->getUser()->getCountryCode(),
            $messengerUser->getUser()->getLocaleCode(),
            telegramBot: $feedbackSearchLookupTransfer->getTelegramBot()
        );
        $this->entityManager->persist($feedbackSearchSearch);

        if ($this->options->shouldLogActivities()) {
            $this->activityLogger->logActivity($feedbackSearchSearch);
        }

        return $feedbackSearchSearch;
    }
}
