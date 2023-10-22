<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\CommandOptions;
use App\Entity\Feedback\FeedbackSearchSearch;
use App\Exception\CommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Service\Feedback\Command\CommandLimitsChecker;
use App\Service\Feedback\Command\CommandStatisticProviderInterface;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermUpserter;
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
        private readonly FeedbackSearchTermUpserter $termUpserter,
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

        $searchTerm = $this->termUpserter->upsertFeedbackSearchTerm($feedbackSearchLookupTransfer->getSearchTerm());

        $feedbackSearchSearch = new FeedbackSearchSearch(
            $messengerUser->getUser(),
            $messengerUser,
            $searchTerm,
            hasActiveSubscription: $hasActiveSubscription,
            countryCode: $messengerUser->getUser()->getCountryCode(),
            localeCode: $messengerUser->getUser()->getLocaleCode(),
            telegramBot: $feedbackSearchLookupTransfer->getTelegramBot()
        );

        $this->entityManager->persist($feedbackSearchSearch);

        if ($this->options->shouldLogActivities()) {
            $this->activityLogger->logActivity($feedbackSearchSearch);
        }

        return $feedbackSearchSearch;
    }
}
