<?php

declare(strict_types=1);

namespace App\Exception\User;

use App\Entity\User\User;
use App\Exception\Exception;
use Throwable;

class SameUserException extends Exception
{
    public function __construct(User $user, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Self user "%s" feedback', $user->getId()), $code, $previous);
    }
}