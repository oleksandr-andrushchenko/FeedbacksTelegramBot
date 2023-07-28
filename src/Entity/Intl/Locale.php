<?php

declare(strict_types=1);

namespace App\Entity\Intl;

readonly class Locale
{
    public function __construct(
        private string $code,
        private string $country,
    )
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}