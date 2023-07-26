<?php

declare(strict_types=1);

namespace App\Exception\Feedback;

use App\Exception\Exception;
use Throwable;

class CreateFeedbackSearchLimitExceeded extends Exception
{
    public function __construct(
        private readonly string $periodKey,
        private readonly int $limit,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct('', $code, $previous);
    }

    public function getPeriodKey(): string
    {
        return $this->periodKey;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
