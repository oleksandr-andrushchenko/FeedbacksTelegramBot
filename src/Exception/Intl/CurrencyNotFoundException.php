<?php

declare(strict_types=1);

namespace App\Exception\Intl;

use App\Exception\Exception;
use Throwable;

class CurrencyNotFoundException extends Exception
{
    public function __construct(string $currency, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" currency has not been found', $currency), $code, $previous);
    }
}
