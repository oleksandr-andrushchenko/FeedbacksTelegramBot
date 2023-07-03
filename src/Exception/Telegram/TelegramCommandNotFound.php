<?php

declare(strict_types=1);

namespace App\Exception\Telegram;

use App\Exception\Exception;
use Throwable;

class TelegramCommandNotFound extends Exception
{
    public function __construct(private readonly ?string $command, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" command has not been found', $this->command), $code, $previous);
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}
