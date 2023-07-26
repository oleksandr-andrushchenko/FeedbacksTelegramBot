<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackUserSubscription>
 *
 * @method FeedbackUserSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedbackUserSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedbackUserSubscription[]    findAll()
 * @method FeedbackUserSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackUserSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackUserSubscription::class);
    }

    public function save(FeedbackUserSubscription $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FeedbackUserSubscription $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTargetUser(User $targetUser): array
    {
        return $this->findBy(['targetUser' => $targetUser]);
    }

//    /**
//     * @return FeedbackUserSubscription[] Returns an array of FeedbackUserSubscription objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FeedbackUserSubscription
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
