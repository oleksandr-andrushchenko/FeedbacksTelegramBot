<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\UserFeedbackMessage;
use App\Exception\ValidatorException;
use App\Object\User\UserFeedbackMessageTransfer;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class UserFeedbackMessageCreator
{
    public function __construct(
        private readonly Validator $validator,
        private readonly EntityManagerInterface $entityManager,
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
        $this->validator->validate($userFeedbackMessageTransfer);

        $message = new UserFeedbackMessage(
            $userFeedbackMessageTransfer->getMessengerUser(),
            $userFeedbackMessageTransfer->getUser(),
            $userFeedbackMessageTransfer->getText()
        );
        $this->entityManager->persist($message);

        return $message;
    }
}