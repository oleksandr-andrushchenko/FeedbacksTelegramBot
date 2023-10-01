<?php

declare(strict_types=1);

namespace App\Service\Google\Api;

use App\Entity\Address\Address;
use App\Entity\Address\AddressComponent;
use App\Entity\Location;
use App\Service\AddressGeocoderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class GoogleAddressGeocoder implements AddressGeocoderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function addressGeocode(Location $location): ?Address
    {
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&key=%s',
            $location->getLatitude(),
            $location->getLongitude(),
            $this->apiKey
        );

        try {
            $json = file_get_contents($url);
            $data = json_decode($json, true);
        } catch (Throwable $exception) {
            $this->logger->error($exception, [
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude(),
            ]);

            return null;
        }

        if (
            !is_array($data)
            || !isset($data['status'])
            || $data['status'] !== 'OK'
            || !isset($data['results'])
            || !is_array($data['results'])
            || count($data['results']) === 0
            || !isset($data['results'][0])
            || !is_array($data['results'][0])
            || !isset($data['results'][0]['address_components'])
            || !is_array($data['results'][0]['address_components'])
        ) {
            return null;
        }

        $addressComponents = $data['results'][0]['address_components'];

        $locality = $this->findAddressComponent('locality', $addressComponents);

        if ($locality === null) {
            return null;
        }

        $region2 = $this->findAddressComponent('administrative_area_level_2', $addressComponents);

        if ($region2 === null) {
            return null;
        }

        $region1 = $this->findAddressComponent('administrative_area_level_1', $addressComponents);

        if ($region1 === null) {
            return null;
        }

        $country = $this->findAddressComponent('country', $addressComponents);

        if ($country === null) {
            return null;
        }

        return new Address(
            strtolower($country->getShortName()),
            $region1,
            $region2,
            $locality,
        );
    }

    private function findAddressComponent(string $type, $addressComponents): ?AddressComponent
    {
        foreach ($addressComponents as $addressComponent) {
            if (in_array($type, $addressComponent['types'], true)) {
                return new AddressComponent($addressComponent['short_name'], $addressComponent['long_name']);
            }
        }

        return null;
    }
}