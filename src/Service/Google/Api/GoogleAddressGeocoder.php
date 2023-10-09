<?php

declare(strict_types=1);

namespace App\Service\Google\Api;

use App\Entity\Address\AddressComponent;
use App\Entity\Address\Address;
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

    public function geocodeAddress(Location $location): ?Address
    {
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&result_type=%s&key=%s',
            $location->getLatitude(),
            $location->getLongitude(),
            implode('|', [
                'country',
                'administrative_area_level_1',
                'administrative_area_level_2',
                'administrative_area_level_3',
            ]),
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
        ) {
            return null;
        }

        $withAdministrativeAreaLevel1 = $this->findByAddressComponents($data['results'], 'administrative_area_level_3');

        if ($withAdministrativeAreaLevel1 !== null) {
            return $withAdministrativeAreaLevel1;
        }

        $withAdministrativeAreaLevel2 = $this->findByAddressComponents($data['results'], 'administrative_area_level_2');

        if ($withAdministrativeAreaLevel2 !== null) {
            return $withAdministrativeAreaLevel2;
        }

        return $this->findByAddressComponents($data['results']);
    }

    private function findByAddressComponents(array $results, string $keyShouldExists = null): ?Address
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

            $administrativeAreaLevel1 = $this->findAddressComponent('administrative_area_level_1', $addressComponents);

            if ($administrativeAreaLevel1 === null) {
                continue;
            }

            $administrativeAreaLevel2 = $this->findAddressComponent('administrative_area_level_2', $addressComponents);

            if ($keyShouldExists === 'administrative_area_level_2' && $administrativeAreaLevel2 === null) {
                continue;
            }

            $administrativeAreaLevel3 = $this->findAddressComponent('administrative_area_level_3', $addressComponents);

            if ($keyShouldExists === 'administrative_area_level_3' && $administrativeAreaLevel3 === null) {
                continue;
            }

            return new Address(
                strtolower($country->getShortName()),
                $administrativeAreaLevel1->getShortName(),
                $administrativeAreaLevel2?->getShortName(),
                $administrativeAreaLevel3?->getShortName(),
            );
        }

        return null;
    }

    private function findAddressComponent(string $type, $addressComponents): ?AddressComponent
    {
        foreach ($addressComponents as $addressComponent) {
            if (in_array($type, $addressComponent['types'], true)) {
                return new AddressComponent($addressComponent['short_name']);
            }
        }

        return null;
    }
}