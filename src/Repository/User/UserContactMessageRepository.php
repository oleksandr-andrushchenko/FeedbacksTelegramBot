<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\UserContactMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserContactMessage>
 *
 * @method UserContactMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserContactMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserContactMessage[]    findAll()
 * @method UserContactMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserContactMessage::class);
    }
}
