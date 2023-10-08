<?php

declare(strict_types=1);

namespace App\Service\Address;

use App\Entity\Address\Address;
use App\Repository\Address\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;

class AddressUpserter
{
    public function __construct(
        private readonly AddressRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function upsertAddress(Address $address): Address
    {
        $existingAddress = $this->repository->findOneByAddress($address);

        if ($existingAddress === null) {
            $this->entityManager->persist($address);

            return $address;
        }

        return $existingAddress;
    }
}