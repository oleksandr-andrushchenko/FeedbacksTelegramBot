<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 *
 * @method Feedback|null find($id, $lockMode = null, $lockVersion = null)
 * @method Feedback|null findOneBy(array $criteria, array $orderBy = null)
 * @method Feedback[]    findAll()
 * @method Feedback[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    public function avgRatingByUser(User $user): ?int
    {
        $rating = $this->createQueryBuilder('f')
            ->select('AVG(f.rating)')
            ->andWhere('f.searchTermUser = :user')
            ->setParameter('user', $user)
            ->andWhere('f.rating <> 0')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if (is_string($rating)) {
            return (int) $rating;
        }

        return $rating;
    }
}
