<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Feedback;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackTransfer;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
    )
    {
    }

    /**
     * @param FeedbackTransfer $feedbackTransfer
     * @return Feedback
     * @throws SameMessengerUserException
     * @throws ValidatorException
     */
    public function createFeedback(FeedbackTransfer $feedbackTransfer): Feedback
    {
        $this->validator->validate($feedbackTransfer);

        $messengerUser = $feedbackTransfer->getMessengerUser();
        $searchTermTransfer = $feedbackTransfer->getSearchTerm();

        if (
            $messengerUser?->getUsername() !== null
            && $messengerUser?->getMessenger() !== null
            && $searchTermTransfer?->getMessengerUsername() !== null
            && strcasecmp($messengerUser->getUsername(), $searchTermTransfer->getMessengerUsername()) === 0
            && $messengerUser->getMessenger() === $searchTermTransfer?->getMessenger()
        ) {
            throw new SameMessengerUserException($messengerUser);
        }

        $feedback = new Feedback(
            $messengerUser,
            $searchTermTransfer->getText(),
            $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText(),
            $searchTermTransfer->getType(),
            null,
            $searchTermTransfer->getMessenger(),
            $searchTermTransfer->getMessengerUsername(),
            $feedbackTransfer->getRating(),
            $feedbackTransfer->getDescription()
        );
        $this->entityManager->persist($feedback);

        return $feedback;
    }
}
