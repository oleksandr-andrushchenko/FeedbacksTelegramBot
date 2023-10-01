<?php

declare(strict_types=1);

namespace App\Service\Address;

use App\Entity\Address\Address;
use App\Entity\Address\AddressLocality;
use App\Repository\Address\AddressLocalityRepository;
use Doctrine\ORM\EntityManagerInterface;

class AddressLocalityUpserter
{
    public function __construct(
        private readonly AddressLocalityRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function upsertAddressLocality(Address $address): AddressLocality
    {
        $addressLocality = $this->repository->findOneByAddress($address);

        if ($addressLocality === null) {
            $addressLocality = new AddressLocality(
                $address->getCountryCode(),
                $address->getRegion1()->getShortName(),
                $address->getRegion2()->getShortName(),
                $address->getLocality()->getShortName(),
            );
            $this->entityManager->persist($addressLocality);
        }

        $addressLocality->intCount();

        return $addressLocality;
    }
}