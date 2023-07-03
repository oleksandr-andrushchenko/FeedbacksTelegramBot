<?php

declare(strict_types=1);

namespace App\Exception\Telegram;

use App\Exception\Exception;
use Longman\TelegramBot\Entities\Update;
use Throwable;

class FailedTelegramRequestInsertException extends Exception
{
    public function __construct(Update $update, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Telegram "%d" request insert failed', $update->getUpdateId()), $code, $previous);
    }
}