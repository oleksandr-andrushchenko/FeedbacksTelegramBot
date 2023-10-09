<?php

declare(strict_types=1);

namespace App\Repository\Address;

use App\Entity\Address\Address;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 *
 * @method Address|null find($id, $lockMode = null, $lockVersion = null)
 * @method Address|null findOneBy(array $criteria, array $orderBy = null)
 * @method Address[]    findAll()
 * @method Address[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function findOneByAddress(Address $address): ?Address
    {
        $addresses = $this->findBy([
            'administrativeAreaLevel1' => $address->getAdministrativeAreaLevel1(),
        ]);

        foreach ($addresses as $existingAddress) {
            if ($existingAddress->getCountryCode() !== $address->getCountryCode()) {
                continue;
            }

            if ($existingAddress->getAdministrativeAreaLevel2() !== $address->getAdministrativeAreaLevel2()) {
                continue;
            }

            if ($existingAddress->getAdministrativeAreaLevel3() !== $address->getAdministrativeAreaLevel3()) {
                continue;
            }

            return $existingAddress;
        }

        return null;
    }
}
