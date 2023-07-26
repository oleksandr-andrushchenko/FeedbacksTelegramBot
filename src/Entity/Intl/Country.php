<?php

declare(strict_types=1);

namespace App\Entity\Intl;

readonly class Country
{
    public function __construct(
        private string $code,
        private string $currency,
        private array $languageCodes
    )
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getLanguageCodes(): array
    {
        return $this->languageCodes;
    }
}