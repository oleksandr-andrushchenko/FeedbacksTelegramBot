<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearchSearch;
use App\Entity\Feedback\FeedbackSearchSearchCreatorOptions;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchSearchTransfer;
use App\Service\Logger\ActivityLogger;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackSearchSearchCreator
{
    public function __construct(
        private readonly FeedbackSearchSearchCreatorOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly ActivityLogger $activityLogger,
    )
    {
    }

    /**
     * @param FeedbackSearchSearchTransfer $feedbackSearchLookupTransfer
     * @return FeedbackSearchSearch
     * @throws ValidatorException
     */
    public function createFeedbackSearchSearch(FeedbackSearchSearchTransfer $feedbackSearchLookupTransfer): FeedbackSearchSearch
    {
        $this->validator->validate($feedbackSearchLookupTransfer);

        $messengerUser = $feedbackSearchLookupTransfer->getMessengerUser();
        $searchTermTransfer = $feedbackSearchLookupTransfer->getSearchTerm();
        $searchTermMessengerUser = null;

        $feedbackSearchLookup = new FeedbackSearchSearch(
            $messengerUser->getUser(),
            $messengerUser,
            $searchTermTransfer->getText(),
            $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText(),
            $searchTermTransfer->getType(),
            $searchTermMessengerUser,
            $searchTermTransfer->getMessenger(),
            $searchTermTransfer->getMessengerUsername(),
            $messengerUser->getUser()->getCountryCode(),
            $messengerUser->getUser()->getLocaleCode()
        );
        $this->entityManager->persist($feedbackSearchLookup);

        if ($this->options->logActivities()) {
            $this->activityLogger->logActivity($feedbackSearchLookup);
        }

        return $feedbackSearchLookup;
    }
}
