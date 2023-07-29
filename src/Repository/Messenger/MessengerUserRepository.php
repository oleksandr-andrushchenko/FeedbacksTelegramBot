<?php

declare(strict_types=1);

namespace App\Repository\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Enum\Messenger\Messenger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method MessengerUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessengerUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessengerUser[]    findAll()
 * @method MessengerUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessengerUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessengerUser::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByMessengerAndUsername(Messenger $messenger, string $username): ?MessengerUser
    {
        return $this->findOneBy([
            'messenger' => $messenger,
            'username' => $username,
        ]);
    }

    public function findOneByMessengerAndIdentifier(Messenger $messenger, string $identifier): ?MessengerUser
    {
        return $this->findOneBy([
            'messenger' => $messenger,
            'identifier' => $identifier,
        ]);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy([
            'user' => $user,
        ]);
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
