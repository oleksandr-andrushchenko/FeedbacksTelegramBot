<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Location;

interface TimezoneGeocoderInterface
{
    public function timezoneGeocode(Location $location, int $timeztamp = null): ?string;
}