<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Location;

interface TimezoneGeocoderInterface
{
    public function geocodeTimezone(Location $location, int $timestamp = null): ?string;
}