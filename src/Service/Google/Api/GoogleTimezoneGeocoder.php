<?php

declare(strict_types=1);

namespace App\Service\Google\Api;

use App\Entity\Location;
use App\Service\TimezoneGeocoderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class GoogleTimezoneGeocoder implements TimezoneGeocoderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function timezoneGeocode(Location $location, int $timestamp = null): ?string
    {
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/timezone/json?location=%s%s%s&timestamp=%s&key=%s',
            $location->getLatitude(),
            '%2C',
            $location->getLongitude(),
            $timestamp ?? time(),
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

        return $data['timeZoneId'] ?? null;
    }
}