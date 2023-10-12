<?php

declare(strict_types=1);

namespace App\Service\Google\Api;

use App\Entity\Address\AddressComponent;
use App\Entity\Address\Address;
use App\Entity\Location;
use App\Exception\AddressGeocodeFailedException;
use App\Service\AddressGeocoderInterface;

class GoogleAddressGeocoder implements AddressGeocoderInterface
{
    public function __construct(
        private readonly string $apiKey,
    )
    {
    }

    public function geocodeAddress(Location $location): Address
    {
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&result_type=%s&key=%s',
            $location->getLatitude(),
            $location->getLongitude(),
            implode('|', [
                'country',
                'administrative_area_level_1',
            ]),
            $this->apiKey
        );

        $json = file_get_contents($url);

        if ($json === false) {
            throw new AddressGeocodeFailedException($location);
        }

        $data = json_decode($json, true);

        if (
            !is_array($data)
            || !isset($data['status'])
            || $data['status'] !== 'OK'
            || !isset($data['results'])
            || !is_array($data['results'])
            || count($data['results']) === 0
        ) {
            throw new AddressGeocodeFailedException($location, $json);
        }

        $address = $this->findByAddressComponents($data['results']);

        if ($address === null) {
            throw new AddressGeocodeFailedException($location, $json);
        }

        return $address;
    }

    private function findByAddressComponents(array $results): ?Address
    {
        foreach ($results as $result) {
            if (
                !is_array($result)
                || !isset($result['address_components'])
                || !is_array($result['address_components'])
            ) {
                continue;
            }

            $addressComponents = $result['address_components'];

            $country = $this->findAddressComponent('country', $addressComponents);

            if ($country === null) {
                continue;
            }

            $countryCode = $country->getShortName();

            if ($this->hasNonAlpha($countryCode)) {
                continue;
            }

            $administrativeAreaLevel1 = $this->findAddressComponent('administrative_area_level_1', $addressComponents);

            if ($administrativeAreaLevel1 === null) {
                continue;
            }

            $level1RegionCode = $administrativeAreaLevel1->getShortName();

            if ($this->hasNonAlpha($level1RegionCode)) {
                $level1RegionCode = $administrativeAreaLevel1->getLongName();
            }

            if ($this->hasNonAlpha($level1RegionCode)) {
                continue;
            }

            return new Address(
                strtolower($countryCode),
                $this->keepAlphaOnly($level1RegionCode),
            );
        }

        return null;
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

    private function hasNonAlpha(string $text): bool
    {
        return preg_match('/[^a-zA-Z-\s\–]/i', $text) > 0;
    }

    private function keepAlphaOnly(string $text): string
    {
        return preg_replace('/[\–]/i', '-', $text);
    }
}