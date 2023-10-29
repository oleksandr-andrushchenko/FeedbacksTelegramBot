<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address\Address;
use App\Entity\Location;
use App\Exception\AddressGeocodeFailedException;

interface AddressGeocoderInterface
{
    /**
     * @param Location $location
     * @return Address
     * @throws AddressGeocodeFailedException
     */
    public function geocodeAddress(Location $location): Address;
}