<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchCreatorOptions;
use App\Exception\Feedback\CreateFeedbackSearchLimitExceeded;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchTransfer;
use App\Service\Logger\ActivityLogger;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackSearchCreator
{
    public function __construct(
        private readonly FeedbackSearchCreatorOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly UserCreateFeedbackSearchStatisticsProvider $userStatisticsProvider,
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
        private readonly ActivityLogger $activityLogger,
    )
    {
    }

    public function getOptions(): FeedbackSearchCreatorOptions
    {
        return $this->options;
    }

    /**
     * @param FeedbackSearchTransfer $feedbackSearchTransfer
     * @return FeedbackSearch
     * @throws CreateFeedbackSearchLimitExceeded
     * @throws ValidatorException
     */
    public function createFeedbackSearch(FeedbackSearchTransfer $feedbackSearchTransfer): FeedbackSearch
    {
        $this->validator->validate($feedbackSearchTransfer);

        $messengerUser = $feedbackSearchTransfer->getMessengerUser();
        $isPremium = $this->userSubscriptionManager->hasActiveSubscription($messengerUser);

        if (!$isPremium) {
            $this->checkLimits($feedbackSearchTransfer);
        }

        $searchTermTransfer = $feedbackSearchTransfer->getSearchTerm();
        $searchTermMessengerUser = null;

        $feedbackSearch = new FeedbackSearch(
            $messengerUser->getUser(),
            $messengerUser,
            $searchTermTransfer->getText(),
            $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText(),
            $searchTermTransfer->getType(),
            $searchTermMessengerUser,
            $searchTermTransfer->getMessenger(),
            $searchTermTransfer->getMessengerUsername(),
            $isPremium,
            $messengerUser->getUser()?->getCountryCode()
        );
        $this->entityManager->persist($feedbackSearch);

        if ($this->options->logActivities()) {
            $this->activityLogger->logActivity($feedbackSearch);
        }

        return $feedbackSearch;
    }

    /**
     * @param FeedbackSearchTransfer $feedbackSearchTransfer
     * @return void
     * @throws CreateFeedbackSearchLimitExceeded
     */
    private function checkLimits(FeedbackSearchTransfer $feedbackSearchTransfer): void
    {
        $user = $feedbackSearchTransfer->getMessengerUser()->getUser();
        $periodLimits = [
            'day' => $this->options->userPerDayLimit(),
            'month' => $this->options->userPerMonthLimit(),
            'year' => $this->options->userPerYearLimit(),
        ];
        $periodCounts = $this->userStatisticsProvider->getUserCreateFeedbackSearchStatistics(array_keys($periodLimits), $user);

        foreach ($periodCounts as $periodKey => $periodCount) {
            if ($periodCount >= $periodLimits[$periodKey]) {
                throw new CreateFeedbackSearchLimitExceeded($periodKey, $periodLimits[$periodKey]);
            }
        }
    }
}
