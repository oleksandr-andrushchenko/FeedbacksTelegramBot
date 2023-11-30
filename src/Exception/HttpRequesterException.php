<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class HttpRequesterException extends Exception
{
    public function __construct(
        private readonly string $method,
        private readonly string $url,
        private readonly int $statusCode,
        ?Throwable $previous = null
    )
    {
        parent::__construct(sprintf('%d status code received for "%s %s"', $this->statusCode, $this->method, $url), 0, $previous);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}