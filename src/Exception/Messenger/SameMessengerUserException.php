<?php

declare(strict_types=1);

namespace App\Exception\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Exception\Exception;
use Throwable;

class SameMessengerUserException extends Exception
{
    public function __construct(private readonly MessengerUser $messengerUser, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($this->buildMessage(), $code, $previous);
    }

    public function getMessengerUser(): MessengerUser
    {
        return $this->messengerUser;
    }

    private function buildMessage(): string
    {
        return sprintf(
            'Self messenger user "%s/%s" feedback',
            $this->messengerUser->getUsername(),
            $this->messengerUser->getMessenger()->name
        );
    }
}