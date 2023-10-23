<?php

declare(strict_types=1);

namespace App\Service\Address;

use App\Entity\Address\Level1Region;
use App\Entity\Location;
use App\Exception\AddressGeocodeFailedException;
use App\Exception\TimezoneGeocodeFailedException;
use App\Repository\Address\Level1RegionRepository;
use App\Service\AddressGeocoderInterface;
use App\Service\TimezoneGeocoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Level1RegionProvider
{
    public function __construct(
        private readonly AddressGeocoderInterface $addressGeocoder,
        private readonly TimezoneGeocoderInterface $timezoneGeocoder,
        private readonly Level1RegionUpserter $upserter,
        private readonly Level1RegionRepository $repository,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    /**
     * @param Location $location
     * @return Level1Region
     * @throws AddressGeocodeFailedException
     * @throws TimezoneGeocodeFailedException
     */
    public function getLevel1RegionByLocation(Location $location): Level1Region
    {
        $address = $this->addressGeocoder->geocodeAddress($location);
        $level1Region = $this->upserter->upsertLevel1RegionByAddress($address);

        if ($level1Region->getTimezone() === null) {
            $timezone = $this->timezoneGeocoder->geocodeTimezone($location);
            $level1Region->setTimezone($timezone);
        }

        return $level1Region;
    }

    public function getLevel1RegionByCountryAndName(string $countryCode, string $name, string $timezone = null): ?Level1Region
    {
        $level1Region = $this->upserter->upsertLevel1RegionByCountryAndName($countryCode, $name);

        if ($level1Region->getTimezone() === null && $timezone !== null) {
            $level1Region->setTimezone($timezone);
        }

        return $level1Region;
    }

    /**
     * @param string $countryCode
     * @return Level1Region[]
     */
    public function getLevel1Regions(string $countryCode): array
    {
        return $this->repository->findByCountry($countryCode);
    }

    public function getLevel1RegionNameById(string $level1RegionId): ?string
    {
        $level1Region = $this->repository->find($level1RegionId);

        return $this->getLevel1RegionName($level1Region);
    }

    public function getLevel1RegionName(Level1Region $level1Region, string $localeCode = null): ?string
    {
        return $this->translator->trans(
            $level1Region->getName(),
            domain: sprintf('level_1_region.%s', $level1Region->getCountryCode()),
            locale: $localeCode
        );
    }
}