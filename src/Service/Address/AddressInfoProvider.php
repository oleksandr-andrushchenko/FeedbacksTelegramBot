<?php

declare(strict_types=1);

namespace App\Service\Address;

use App\Entity\Address\Address;

class AddressInfoProvider
{
    public function getAddressInfo(Address $address): array
    {
        return [
            'country' => $address->getCountryCode(),
            'administrative_area_level_1' => $address->getAdministrativeAreaLevel1(),
            'administrative_area_level_2' => $address->getAdministrativeAreaLevel2() ?? 'N/A',
            'administrative_area_level_3' => $address->getAdministrativeAreaLevel3() ?? 'N/A',
            'timezone' => $address->getTimezone() ?? 'N/A',
            'count' => $address->getCount(),
            'created_at' => $address->getCreatedAt()->format('Y-m-d H:i'),
            'updated_at' => $address->getUpdatedAt() === null ? 'N/A' : $address->getUpdatedAt()->format('Y-m-d H:i'),
            'deleted_at' => $address->getDeletedAt() === null ? 'N/A' : $address->getDeletedAt()->format('Y-m-d H:i'),
        ];
    }
}