<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address\Address;
use App\Entity\Location;

interface AddressGeocoderInterface
{
    public function addressGeocode(Location $location): ?Address;
}