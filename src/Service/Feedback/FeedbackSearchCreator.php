<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchTransfer;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackSearchCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
    )
    {
    }

    /**
     * @param FeedbackSearchTransfer $feedbackSearchTransfer
     * @return FeedbackSearch
     * @throws ValidatorException
     */
    public function createFeedbackSearch(FeedbackSearchTransfer $feedbackSearchTransfer): FeedbackSearch
    {
        $this->validator->validate($feedbackSearchTransfer);

        $messengerUser = $feedbackSearchTransfer->getMessengerUser();
        $searchTermTransfer = $feedbackSearchTransfer->getSearchTerm();
        $searchTermMessengerUser = null;

        $feedbackSearch = new FeedbackSearch(
            $messengerUser,
            $searchTermTransfer->getText(),
            $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText(),
            $searchTermTransfer->getType(),
            $searchTermMessengerUser,
            $searchTermTransfer->getMessenger(),
            $searchTermTransfer->getMessengerUsername(),
        );
        $this->entityManager->persist($feedbackSearch);

        return $feedbackSearch;
    }
}
