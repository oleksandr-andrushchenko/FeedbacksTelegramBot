<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\CommandOptions;
use App\Entity\Feedback\FeedbackSearch;
use App\Exception\CommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Service\Feedback\Command\CommandLimitsChecker;
use App\Service\Feedback\Command\CommandStatisticProviderInterface;
use App\Service\Feedback\SearchTerm\FeedbackSearchTermUpserter;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Logger\ActivityLogger;
use App\Service\Validator;
use App\Transfer\Feedback\FeedbackSearchTransfer;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackSearchCreator
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
     * @param FeedbackSearchTransfer $feedbackSearchTransfer
     * @return FeedbackSearch
     * @throws CommandLimitExceededException
     * @throws ValidatorException
     */
    public function createFeedbackSearch(FeedbackSearchTransfer $feedbackSearchTransfer): FeedbackSearch
    {
        $this->validator->validate($feedbackSearchTransfer);

        $messengerUser = $feedbackSearchTransfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        if (!$hasActiveSubscription) {
            $this->limitsChecker->checkCommandLimits($messengerUser->getUser(), $this->statisticProvider);
        }

        $searchTerm = $this->termUpserter->upsertFeedbackSearchTerm($feedbackSearchTransfer->getSearchTerm());

        $feedbackSearch = new FeedbackSearch(
            $messengerUser->getUser(),
            $messengerUser,
            $searchTerm,
            hasActiveSubscription: $hasActiveSubscription,
            countryCode: $messengerUser->getUser()->getCountryCode(),
            localeCode: $messengerUser->getUser()->getLocaleCode(),
            telegramBot: $feedbackSearchTransfer->getTelegramBot()
        );

        $this->entityManager->persist($feedbackSearch);

        if ($this->options->shouldLogActivities()) {
            $this->activityLogger->logActivity($feedbackSearch);
        }

        return $feedbackSearch;
    }
}
