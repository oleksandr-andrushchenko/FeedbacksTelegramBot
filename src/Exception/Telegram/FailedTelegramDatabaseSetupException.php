<?php

declare(strict_types=1);

namespace App\Exception\Telegram;

use App\Exception\Exception;
use Throwable;

class FailedTelegramDatabaseSetupException extends Exception
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Telegram database setup failed', $code, $previous);
    }
}