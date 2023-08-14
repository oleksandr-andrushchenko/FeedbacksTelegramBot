<?php

declare(strict_types=1);

namespace App\Exception\Telegram\Payment;

use App\Exception\Exception;
use Throwable;

class TelegramPaymentMethodNotFoundException extends Exception
{
    public function __construct(string $name, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" telegram bot payment method has not been found', $name), $code, $previous);
    }
}