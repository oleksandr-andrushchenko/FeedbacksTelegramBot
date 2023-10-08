<?php

declare(strict_types=1);

namespace App\Service\Address;

use App\Entity\Address\Address;
use App\Entity\Location;
use App\Service\AddressGeocoderInterface;
use App\Service\TimezoneGeocoderInterface;

class AddressProvider
{
    public function __construct(
        private readonly AddressGeocoderInterface $addressGeocoder,
        private readonly TimezoneGeocoderInterface $timezoneGeocoder,
        private readonly AddressUpserter $upserter,
    )
    {
    }

    public function getAddress(Location $location): ?Address
    {
        $address = $this->addressGeocoder->addressGeocode($location);

        if ($address === null) {
            return null;
        }

        $address = $this->upserter->upsertAddress($address);

        $timezone = $this->timezoneGeocoder->timezoneGeocode($location);

        $address->setTimezone($timezone);

        return $address;
    }
}