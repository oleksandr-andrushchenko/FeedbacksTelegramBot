<?php

declare(strict_types=1);

namespace App\Exception\Messenger;

use App\Entity\User\User;
use App\Enum\Messenger\Messenger;
use App\Exception\Exception;
use Throwable;

class AbsentMessengerUserException extends Exception
{
    public function __construct(
        private readonly User $user,
        private readonly Messenger $messenger,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(sprintf('No "%s" messenger for "%s" user', $messenger->name, $user->getId()), $code, $previous);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMessenger(): Messenger
    {
        return $this->messenger;
    }
}