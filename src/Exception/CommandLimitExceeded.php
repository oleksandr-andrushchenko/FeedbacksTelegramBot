<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\CommandLimit;
use Throwable;

class CommandLimitExceeded extends Exception
{
    public function __construct(
        private readonly CommandLimit $limit,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct('', $code, $previous);
    }

    public function getLimit(): CommandLimit
    {
        return $this->limit;
    }
}
