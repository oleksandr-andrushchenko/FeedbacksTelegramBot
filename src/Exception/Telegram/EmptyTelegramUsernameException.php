<?php

declare(strict_types=1);

namespace App\Exception\Telegram;

use App\Exception\Exception;
use Throwable;

class EmptyTelegramUsernameException extends Exception
{
    public function __construct(?string $username, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Empty "%s" telegram username', $username), $code, $previous);
    }
}