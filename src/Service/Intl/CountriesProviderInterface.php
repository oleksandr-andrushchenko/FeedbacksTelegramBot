<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Country;

interface CountriesProviderInterface
{
    /**
     * @return Country[]|null
     */
    public function getCountries(): ?array;
}