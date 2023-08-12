<?php

declare(strict_types=1);

namespace App\Exception\Telegram;

use App\Exception\Exception;
use Throwable;

class TelegramGroupNotFoundException extends Exception
{
    public function __construct(string $group, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" telegram bot group has not been found', $group), $code, $previous);
    }
}