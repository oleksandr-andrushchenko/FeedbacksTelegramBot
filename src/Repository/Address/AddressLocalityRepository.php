<?php

declare(strict_types=1);

namespace App\Repository\Address;

use App\Entity\Address\Address;
use App\Entity\Address\AddressLocality;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AddressLocality>
 *
 * @method AddressLocality|null find($id, $lockMode = null, $lockVersion = null)
 * @method AddressLocality|null findOneBy(array $criteria, array $orderBy = null)
 * @method AddressLocality[]    findAll()
 * @method AddressLocality[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressLocalityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AddressLocality::class);
    }

    public function findOneByAddress(Address $address): ?AddressLocality
    {
        $addressLocalities = $this->findBy([
            'locality' => $address->getLocality()->getShortName(),
        ]);

        foreach ($addressLocalities as $addressLocality) {
            if ($addressLocality->getCountryCode() !== $address->getCountryCode()) {
                continue;
            }

            if ($addressLocality->getRegion1() !== $address->getRegion1()->getShortName()) {
                continue;
            }

            if ($addressLocality->getRegion2() !== $address->getRegion2()->getShortName()) {
                continue;
            }

            return $addressLocality;
        }

        return null;
    }
}
