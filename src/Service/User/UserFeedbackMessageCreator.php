<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\UserFeedbackMessage;
use App\Exception\ValidatorException;
use App\Object\User\UserFeedbackMessageTransfer;
use App\Service\Logger\ActivityLogger;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class UserFeedbackMessageCreator
{
    public function __construct(
        private readonly Validator $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    )
    {
    }

    /**
     * @param UserFeedbackMessageTransfer $userFeedbackMessageTransfer
     * @return UserFeedbackMessage
     * @throws ValidatorException
     */
    public function createUserFeedbackMessage(UserFeedbackMessageTransfer $userFeedbackMessageTransfer): UserFeedbackMessage
    {
        // todo: add to activity log
        $this->validator->validate($userFeedbackMessageTransfer);

        $message = new UserFeedbackMessage(
            $userFeedbackMessageTransfer->getMessengerUser(),
            $userFeedbackMessageTransfer->getUser(),
            $userFeedbackMessageTransfer->getText()
        );
        $this->entityManager->persist($message);

        $this->activityLogger->logActivity($message);

        return $message;
    }
}