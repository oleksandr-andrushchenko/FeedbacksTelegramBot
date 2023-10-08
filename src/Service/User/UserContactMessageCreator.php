<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\UserContactMessage;
use App\Exception\ValidatorException;
use App\Transfer\User\UserContactMessageTransfer;
use App\Service\Logger\ActivityLogger;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class UserContactMessageCreator
{
    public function __construct(
        private readonly Validator $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    )
    {
    }

    /**
     * @param UserContactMessageTransfer $userContactMessageTransfer
     * @return UserContactMessage
     * @throws ValidatorException
     */
    public function createUserContactMessage(UserContactMessageTransfer $userContactMessageTransfer): UserContactMessage
    {
        $this->validator->validate($userContactMessageTransfer);

        $message = new UserContactMessage(
            $userContactMessageTransfer->getMessengerUser(),
            $userContactMessageTransfer->getUser(),
            $userContactMessageTransfer->getText(),
            $userContactMessageTransfer->getTelegramBot()
        );
        $this->entityManager->persist($message);

        // todo: fire event and consume
        $this->activityLogger->logActivity($message);

        return $message;
    }
}